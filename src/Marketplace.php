<?php
/**
 * Marketplace plugin for Craft CMS 3.x.
 *
 * Marketplace
 *
 * @link      https://kennethormandy.com
 * @copyright Copyright © 2019–2020 Kenneth Ormandy
 */

namespace kennethormandy\marketplace;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\commerce\Plugin as Commerce;
use craft\commerce\events\RefundTransactionEvent;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use craft\commerce\services\Payments;
use craft\commerce\stripe\base\Gateway as StripeGateway;
use craft\commerce\stripe\events\BuildGatewayRequestEvent;
use craft\commerce\helpers\Currency as CurrencyHelper;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\services\Fields;
use craft\web\UrlManager;
use craft\errors\InvalidFieldException;
use kennethormandy\marketplace\fields\MarketplaceConnectButton as MarketplaceConnectButtonField;
use kennethormandy\marketplace\fields\MarketplacePayee as MarketplacePayeeField;
use kennethormandy\marketplace\models\Settings;
use kennethormandy\marketplace\provider\StripeConnectExpressProvider;
use kennethormandy\marketplace\provider\StripeConnectProvider;
use kennethormandy\marketplace\services\AccountsService;
use kennethormandy\marketplace\services\FeesService;
use kennethormandy\marketplace\services\HandlesService;
use kennethormandy\marketplace\services\PayeesService;
use putyourlightson\logtofile\LogToFile;
use Stripe\Stripe;
use Stripe\Transfer;
use Stripe\BalanceTransaction;
use venveo\oauthclient\base\Provider;
use venveo\oauthclient\controllers\AuthorizeController;
use venveo\oauthclient\events\AuthorizationEvent;
use venveo\oauthclient\events\AuthorizationUrlEvent;
use venveo\oauthclient\events\TokenEvent;
use venveo\oauthclient\Plugin as OAuthPlugin;
use venveo\oauthclient\services\Apps as AppsService;
use venveo\oauthclient\services\Providers;
use yii\base\Event;

class Marketplace extends BasePlugin
{
    const EDITION_LITE = 'lite';
    // NOTE Edition marker doesn’t show, unless there
    //      is more than one edition. https://git.io/JvAIY
    // const EDITION_PRO = 'pro';

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '0.1.0';
    public $hasCpSettings = true;

    /**
     * @var Plugin
     */
    public static $plugin;
    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            // self::EDITION_PRO,
        ];
    }

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->_registerCpRoutes();

        // Register services
        $this->setComponents([
            // TODO Rename to just “handles” for nicer API
            'handlesService' => HandlesService::class,
            'fees' => FeesService::class,
            'payees' => PayeesService::class,
            'accounts' => AccountsService::class,
        ]);

        Craft::info('Marketplace plugin loaded', __METHOD__);

        $this->_reviseOrderTemplate();

        // Register provider
        Event::on(
            Providers::class,
            Providers::EVENT_REGISTER_PROVIDER_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = StripeConnectProvider::class;
                $event->types[] = StripeConnectExpressProvider::class;
            }
        );

        // Register our fields
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = MarketplaceConnectButtonField::class;
                $event->types[] = MarketplacePayeeField::class;
            }
        );

        // Before saving the token, use the response to get the
        // Stirpe Account ID provided by Stripe, and save that to
        // the plugin’s custom field
        Event::on(
            Provider::class,
            Provider::EVENT_CREATE_TOKEN_MODEL_FROM_RESPONSE,
            function (TokenEvent $event) {
                $stripeResponse = $event->responseToken;
                $token = $event->token;

                $this->log('EVENT_CREATE_TOKEN_MODEL_FROM_RESPONSE');
                $this->log(json_encode($stripeResponse));

                if (isset($stripeResponse)) {
                    $this->log('Stripe response');
                    $this->log(json_encode($stripeResponse));

                    if (
                        isset($stripeResponse) &&
                        isset($stripeResponse->stripe_user_id)
                    ) {
                        $this->log('Stripe Account Id stripe_user_id');
                        $stripeAccountId = $stripeResponse->stripe_user_id;

                        // Save the Stripe Account ID on the token
                        // This seems to get overwritten in the token, once it’s save to the DB, but it
                        // works fine for our purposes. Otherwise, could change token->userId to the
                        // element id I want to use, but I think that will cause other problems later, and we
                        // still want to know what user created the token
                        $event->token->uid = $stripeAccountId;
                    }
                }
            }
        );

        Event::on(
            AppsService::class,
            AppsService::EVENT_GET_URL_OPTIONS,
            function (AuthorizationUrlEvent $e) {
                $this->log('EVENT_GET_URL_OPTIONS');
                $this->log(json_encode($e));
                $appHandle = self::$plugin->getSettings()->getAppHandle();

                $this->log('Get App handle ' . $appHandle . ' ' . $e->app->handle);

                // TODO We want to check the handle matches, and the type
                // of provider is our Stripe provider, as you could in theory
                // have an app wit hthe handle `stripe` that matches that is
                // actually the Google provider (or whatever)
                if ($e->app->handle === $appHandle) {
                    if ($e->context && isset($e->context['user'])) {
                        $user = $e->context['user'];
                        $userId = $user['id'];

                        // Set user id that oauth plugin will use to the
                        // profile page’s userId, rather than the logged in
                        // person’s userId
                        // This didn’t actually work
                        // $e->app->userId = $userId;

                        // So we have this when the request comes back
                        $e->options['craft_user_id'] = $userId;
                        $e->options['craft_user_uid'] = $user['uid'];

                        // Add other options to the base url, ex. existing profile into to prefill
                        // https://stripe.com/docs/connect/express-accounts#prefill-form-fields
                        if (isset($user['email'])) {
                            $e->options['stripe_user[email]'] = $user['email'];
                        }

                        if (isset($user['url'])) {
                            $e->options['stripe_user[url]'] = $user['url'];
                        }

                        if (isset($user['firstName'])) {
                            $e->options['stripe_user[first_name]'] = $user['firstName'];
                        }

                        if (isset($user['lastName'])) {
                            $e->options['stripe_user[last_name]'] = $user['lastName'];
                        }
                    }
                }

                return $e;
            }
        );

        Event::on(
            AuthorizeController::class,
            AuthorizeController::EVENT_BEFORE_AUTHENTICATE,
            function (AuthorizationEvent $event) {

                if ($event->context) {
                    $this->log('EVENT_BEFORE_AUTHENTICATE context');
                    $this->log(json_encode($event->context));    
                }

                if (
                $event->context &&
                isset($event->context['location']) &&
                isset($event->context['location']['pathname']) &&
                $event->context['contextName'] === 'MarketplaceConnectButton') {
                    $loc = $event->context['location'];
                    $pathname = $loc['pathname'];
                    if (isset($loc['hash'])) {
                        $pathname = $pathname . $loc['hash'];
                    }

                    $returnUrl = UrlHelper::cpUrl($pathname, null, null);
                    $this->log('Return URL');
                    $this->log($returnUrl);
                    $event->returnUrl = $returnUrl;
                }
            }
        );

        // TODO Here, the uid on the token should be the Stripe Account ID
        // The uid in the context is the API I’m thinking of using for saying
        // “don’t save the Stripe Account ID on the current user, save it on this element”
        Event::on(
            AuthorizeController::class,
            AuthorizeController::EVENT_AFTER_AUTHENTICATE,
            function (AuthorizationEvent $event) {
                $this->log('EVENT_AFTER_AUTHENTICATE context');
                $this->log(json_encode($event));
                $this->log(json_encode($event->context));

                if (
                    is_array($event->context) &&
                    isset($event->context['elementUid']) &&
                    $event->context['elementUid']
                ) {
                    $this->log(json_encode($event->context['elementUid']));
                    $elementUid = $event->context['elementUid'];
    
                    // This needs to be a string for the class, not a simple string like “category”
                    // https://docs.craftcms.com/api/v3/craft-services-elements.html#public-methods
                    $elementType = null;
                    // if (isset($event->context['elementType'])) {
                    //     $this->log(json_encode($event->context['elementType']));
                    //     $elementType = $event->context['elementType'];
                    // }

                    $element = Craft::$app->elements->getElementByUid($elementUid, $elementType);    

                    $this->log('element');
                    $this->log(json_encode($element));
                    $this->log(json_encode($element->slug));

                    $token = $event->token;
                    $this->log(json_encode($token));

                    $stripeConnectHandle = $this->handlesService->getButtonHandle($element);
                    $element->setFieldValue($stripeConnectHandle, $token->uid);

                    Craft::$app->elements->saveElement($element);
                } else {
                    // Didn’t explicitly provide an elementUid to save the
                    // Stripe Connect Account ID to, so we assume that
                    // it should be saved to the current user, if there is
                    // a Marketplace Button Field on the user.

                    // TODO Could set elementUid to the current user uid when the
                    // initial Stripe URL is created, and possibly remove this else
                    // conditional entirely. Otherwise hypothetically you can
                    // be logged in as a different user to initiate the process, and have the
                    // result applied to the wrong user.

                    $token = $event->token;
                    $userId = Craft::$app->user->getId();
                    $userObject = Craft::$app->users->getUserById($userId);
                    $stripeConnectHandle = $this->handlesService->getButtonHandle($userObject);

                    $this->log('Got Marketplace handle ' . $stripeConnectHandle);

                    if ($stripeConnectHandle && $userObject) {
                        try {
                            $userObject->setFieldValue($stripeConnectHandle, $token->uid);
                            Craft::$app->elements->saveElement($userObject);
                        } catch (InvalidFieldException $error) {
                            $this->log(json_encode($error), [], 'error');
                        }
                    }

                }
            }
        );

        // Full and Parial refunds are supported here.
        // TODO Could provide more options around this, ex: Who is responsible
        // for the refund, the platform or connected account? Should the
        // platform application fee be refunded? (Currently, it is not, so the
        // connected account ends up paying the platform for the refund—which
        // might be appropriate in some situations, but not others.
        Event::on(
            Payments::class,
            Payments::EVENT_BEFORE_REFUND_TRANSACTION,
            function (RefundTransactionEvent $e) {
                $this->log('EVENT_BEFORE_REFUND_TRANSACTION');

                // We are assuming all of these are destination charges,
                // might need to find some way to look at the charge and
                // see if we can tell which one it was (no good checking the
                // settings, because it could have changed since the order
                // was made).

                $secretApiKey = self::$plugin->getSettings()->getSecretApiKey();
                Stripe::setApiKey($secretApiKey);

                if (isset($e->transaction->response)) {
                    $res = json_decode($e->transaction->response);

                    // In progress:
                    $this->log('[Stripe refund] ' . $e->transaction->response);

                    if (isset($res->charges) && isset($res->charges->data) && count($res->charges->data) >= 1) {
                        $originalCharge = $res->charges->data[0];

                        if (isset($originalCharge->transfer)) {
                            $transferId = $originalCharge->transfer;

                            // TODO This should be the filled in refund amount, otherwise this
                            $refundAmount = (int) $originalCharge->amount;

                            if ($refundAmount > 0) {
                                Transfer::createReversal(
                                    $transferId,
                                    ['amount' => $refundAmount]
                                );
                            }
                        }
                    }
                }
            }
        );

        Event::on(
            StripeGateway::class,
            StripeGateway::EVENT_BUILD_GATEWAY_REQUEST,
            function (BuildGatewayRequestEvent $e) {
                if ($this->isPro() && $this->getSettings()->stripePreferSeparateTransfers) {
                    return;
                }

                // TODO Temporary hard-coded config
                // Not supporting direct charges for now, looks like it
                // would require a change to Craft Commerce Stripe gateway
                // $hardCodedApproach = 'direct-charge';
                $hardCodedApproach = 'destination-charge';
                $applicationFeeAmount = 0;

                $applicationFees = self::$plugin->fees->getAllFees();

                // Make Destination Charges behave more like Direct Charges
                // https://stripe.com/docs/payments/connected-accounts#charge-on-behalf-of-a-connected-account
                $hardCodedOnBehalfOf = false;

                if ($e->transaction->type !== 'purchase' && $e->transaction->type !== 'authorize') {
                    $this->log(
                        'Unsupported transaction type: ' . $e->transaction->type
                    );

                    return;
                }


                $order = $e->transaction->order;

                if (!$order || !$order->lineItems || count($order->lineItems) == 0) {
                    return;
                }

                // By default only supports one line item, to match Commerce Lite
                $lineItemOnly = $order->lineItems[0];
                $payeeStripeAccountId = $this->payees->getGatewayAccountId($lineItemOnly);

                if (!$payeeStripeAccountId) {
                    $this->log(
                        '[Order #' . $order->id . '] Stripe ' . $hardCodedApproach . ' no User Payee Account ID. Paying to parent account.'
                    );

                    return;
                }

                // If there’s more than one line item, we check they all have the
                // same payees. In Lite, we’ll allow the payment splitting as long as
                // they all match. In Pro, we’ll split payments.
                if (count($order->lineItems) > 1) {
                    $payeesSame = $this->_checkAllPayeesSame($order);

                    if (!$payeesSame) {
                        // If it’s the Lite edition, but payees are not the same,
                        // we don’t support this scenario. Instead, we return,
                        // which means the transaction will go through as a
                        // normal Craft Commerce transaction.
                        if (!$this->isPro()) {
                            $this->log(
                                'Stripe ' . $hardCodedApproach . ' line items have different User Payee Account IDs. Paying to parent account.'
                            );
                        }

                        // If it’s the Pro edition, the remainder is handled after payment.
                        return;
                    }
                }

                $this->log(
                    'Stripe ' . $hardCodedApproach . ' to Stripe Account ID: ' . $payeeStripeAccountId
                );

                if ($hardCodedApproach === 'destination-charge') {
                    $this->log(
                        '[Marketplace request] [' . $hardCodedApproach . '] ' . json_encode($e->request)
                    );

                    $this->log(
                        '[Marketplace request] [' . $hardCodedApproach . '] destination ' . $payeeStripeAccountId
                    );

                    // Fees are always based on lineItems
                    foreach ($order->lineItems as $lineItemId => $lineItem) {
                        $lineItemFeeAmount = $this->fees->calculateFeesAmount($lineItem, $order);
                        $applicationFeeAmount = $applicationFeeAmount + $lineItemFeeAmount;
                    }

                    if ($applicationFeeAmount) {
                        $stripeApplicationFeeAmount = $this->_toStripeAmount($applicationFeeAmount, $order->paymentCurrency);
                        $e->request['application_fee_amount'] = $stripeApplicationFeeAmount;
                    }

                    if ($hardCodedOnBehalfOf) {
                        $e->request['on_behalf_of'] = $payeeStripeAccountId;
                    }

                    // https://stripe.com/docs/connect/quickstart#process-payment
                    // https://stripe.com/docs/connect/destination-charges
                    $e->request['transfer_data'] = [
                        'destination' => $payeeStripeAccountId,
                    ];

                    $this->log(
                        '[Marketplace request modified] [' . $hardCodedApproach . '] ' . json_encode($e->request)
                    );
                } elseif ($hardCodedApproach === 'direct-charge') {

                // This doesn’t work by default
                // Modifying the plugin to support this
                // $e->request['stripe_account'] = $hardCodedConnectedAccountId;

                // in commerce-stripe/src/gateways/PaymentIntents.php I added:

                // $secondArgs = ['idempotency_key' => $transaction->hash];
                // if ($requestData['stripe_account']) {
                //   $secondArgs['stripe_account'] = $requestData['stripe_account'];
                //   unset($requestData['stripe_account']);
                // }

                // …and then added $secondArgs as the second arg to the change.

                // That worked, but then hit another error. You have
                // to fully auth using publishableKey and secretKey
                // for the connected account to use direct charges,
                // so you probably need the full OAuth flow to get those
                //   Although also confused because the example does
                // show them using the platform key.

                // Can use this to behave more like direct-charge,
                // changed to do this via $hardCodedOnBehalfOf because it
                // it still seems to be to do with desitnation charges rather than
                // direct charges, which presumably wouldn’t take an application fee either
                // https://stripe.com/docs/payments/payment-intents/use-cases#connected-accounts
                // https://stripe.com/docs/payments/connected-accounts#charge-on-behalf-of-a-connected-account
                // $e->request['on_behalf_of'] = $payeeStripeAccountId;
                // $e->request['transfer_data'] = [
                //   "destination" => $payeeStripeAccountId
                // ];
                }
            }
        );

        Event::on(
            Order::class,
            Order::EVENT_AFTER_COMPLETE_ORDER,
            function(Event $event) {
                /** @var Order $order */
                $order = $event->sender;
                $purchaseTransaction = null;

                if (!$this->isPro()) {
                    return;
                }

                if ($this->getSettings()->stripePreferSeparateTransfers === false) {
                    if (1 >= count($order->lineItems)) {
                        return;
                    }

                    // TODO Can we pass this along in the order snapshot,
                    // rather than needing to recalculate it?
                    $payeesSame = $this->_checkAllPayeesSame($order);

                    // If they are the same, we already made the transfer as part of payment
                    if ($payeesSame) {
                        return;
                    }
                }

                foreach ($order->transactions as $transaction) {
                    // Stop at the first successful transaction, can also be failed
                    // TODO Does auth and capture still create this?
                    if ($transaction->type === 'purchase' && $transaction->status === 'success') {
                        $purchaseTransaction = $transaction;                        
                        break;
                    }
                }

                if (!$purchaseTransaction || !$purchaseTransaction->reference) {
                    $this->log('No purchase transaction found on Order ' . $order->id, [], 'error');
                    return;
                }

                $stripeResp = json_decode($purchaseTransaction->response);
                $currencyCountryCode = $purchaseTransaction->paymentCurrency;

                $this->log('Original transaction currency: ' . $currencyCountryCode);
                $this->log('Transaction:');
                $this->log(json_encode($purchaseTransaction));


                $stripeCharge = null;

                // Get the first captured transaction
                if (
                    isset($stripeResp->charges) && $stripeResp->charges &&
                    isset($stripeResp->charges->data) && $stripeResp->charges->data &&
                    count($stripeResp->charges->data) >= 1
                ) {
                    foreach ($stripeResp->charges->data as $charge) {
                        $this->log(json_encode($charge));
                        if ($charge && $charge->captured && $charge->status === 'succeeded') {
                            $stripeCharge = $charge;
                            break;
                        }
                    }
                }

                if (!$stripeCharge) {
                    $this->log('No successful charge found on Order ' . $order->id, [], 'error');
                    return;
                }

                $this->log('Charge:');
                $this->log(json_encode($stripeCharge));

                try {
                    $balanceTransaction = BalanceTransaction::retrieve($stripeCharge->balance_transaction);
                    $this->log('Balance transaction:');
                    $this->log(json_encode($balanceTransaction));    
                } catch (\Exception $e) {
                    $this->log('Marketplace transfer error', [], 'error');
                    $this->log($e->getTraceAsString(), [], 'error');
                }

                $exchangeRate = $this->_getStripeExchangeRate($balanceTransaction, $currencyCountryCode);
                $this->log('$exchangeRate: ' . $exchangeRate);

                foreach ($order->lineItems as $key => $lineItem) {
                    $payeeCurrent = $this->payees->getGatewayAccountId($lineItem);

                    $this->log('lineItem');
                    $this->log(json_encode($lineItem));

                    // If there isn’t a payee or a total on this line item, nothing to do
                    if (!$payeeCurrent || $lineItem->total === (float) 0) {
                        continue;
                    }

                    $this->log('Craft amount before currency conversion: ' . $lineItem->total);

                    $this->log('$lineItem->total: '. $lineItem->total);
                    $this->log('$order->paymentCurrency: '. $order->paymentCurrency);

                    $craftBaseAndPaymentCurrencyDiffer = $order->paymentCurrency !== $order->currency;

                    if ($craftBaseAndPaymentCurrencyDiffer) {
                        $lineItemTotal = CurrencyHelper::formatAsCurrency(
                            $amount = $lineItem->total,
                            $currency = $order->paymentCurrency,
                            $convert = true,
                            $format = false
                        );                            
                    }

                    $this->log('$lineItemTotal: '. $lineItemTotal);

                    // Calculate LineItem fee
                    // This will change to calculateFeesAmount once it is properly finished
                    // TODO To finish this, need to support flat-fee at line item level
                    // Right now, we apply the flat fee to the first line item, which works if you are using
                    // the application_fee for one Payee, but doesn’t make sense if you have multiple payees
                    // to apply that flat fee to. Could either
                    // - Apply the flat fee once per line item (breaking change for Lite with multiple line items, same payees)
                    // - Apply the flat fee once per payee (same behaviour as lite), and possibly add a new fee type to support the other use case
                    $feeAmountLineItem = $this->fees->_calculateLineItemFeesAmount($lineItem, $order);

                    $this->log('$feeAmountLineItem before Craft conversion'. $feeAmountLineItem);

                    if ($craftBaseAndPaymentCurrencyDiffer) {
                        $feeAmountLineItem = CurrencyHelper::formatAsCurrency(
                            $amount = $feeAmountLineItem,
                            $currency = $order->paymentCurrency,
                            $convert = true,
                            $format = false
                        );    
                    }
                    
                    $this->log('$feeAmountLineItem after Craft conversion'. $feeAmountLineItem);

                    if ($feeAmountLineItem) {
                        $lineItemTotal = $lineItemTotal - $feeAmountLineItem;
                    }

                    // Don’t touch the subtotal, unless we really to have to
                    if ($craftBaseAndPaymentCurrencyDiffer) {
                        $feeAmountLineItem = $feeAmountLineItem * $exchangeRate;
                        $this->log('$lineItemTotal: ' . $lineItemTotal);
                        $this->log('$lineItem->total: ' . $lineItem->total);
                        $lineItemTotal = $lineItemTotal * $exchangeRate;

                    } elseif ($exchangeRate && $exchangeRate !== 1) {

                        // This seems wrong? Should be $lineItemTotal * $exchangeRate?
                        // But I think we don’t ever hit this conditional right now on ILT,
                        // because we didn’t have full base currency vs full currency support
                        // OR it was started but not completed because they added a US bank account
                        // part way through?
                        $lineItemTotal = $lineItem->total * $exchangeRate;
                    }

                    $this->log('Craft amount after currency conversion: ' . $lineItemTotal);

                    $stripeAmount = $this->_toStripeAmount($lineItemTotal, $currencyCountryCode);
                    $this->log('Stripe amount: ' . $stripeAmount);
                    
                    $this->log('In progress: Create transfer for ' . $payeeCurrent);

                    $stripeTransferData = [
                        'amount' => $stripeAmount,

                        // Have to use the balance transaction currency
                        // Ex. If the platform is using GBP (the settlement
                        // currency), and the customer purchased using USD (the
                        // presettlement currency), the balance transaction
                        // and future payout will be in GBP, and therefore the
                        // transfer has to be in GBP as well.
                        'currency' => $balanceTransaction->currency,

                        'destination' => $payeeCurrent,

                        // Don’t need to create a `transfer_group`, Stripe
                        // does this via the source_transaction
                        'source_transaction' => $stripeCharge->id
                    ];

                    $this->log(json_encode($stripeTransferData));

                    try {
                        $transferResult = Transfer::create($stripeTransferData);
                        $this->log('Transfer Result');
                        $this->log(json_encode($transferResult));    
                    } catch (\Exception $e) {
                        throw $e; // Temp
                        $this->log('Marketplace transfer error', [], 'error');
                        $this->log($e->getTraceAsString(), [], 'error');
                    }
                }
            }
        );
    }

    /**
     * Log wrapper
     * 
     * @param string $msg
     * @param array $params
     * @param string $level
     */
    public function log($msg, array $params = [], $level = 'info')
    {
        // TODO Use Craft::t and params

        /** @see https://www.yiiframework.com/doc/api/2.0/yii-log-logger#log()-detail */
        switch ($level) {
            case 'error':
                Craft::error($msg, __METHOD__);
            case 'warning':
                Craft::warning($msg, __METHOD__);
                break;
            default:
                Craft::info($msg, __METHOD__);
                break;
        }

        // Right now, we are just double logging. Might prefer to move to this solution
        // https://craftcms.stackexchange.com/a/25430/6392
        LogToFile::log($msg, 'marketplace', $level);
    }

    /**
     * @param Order $order
     */
    private function _checkAllPayeesSame($order)
    {
        /** @var LineItem[] */
        $lineItems = $order->lineItems;

        if (!isset($lineItems) || !$lineItems) {
            return null;
        }

        if (count($lineItems) <= 1) {
            return true;
        }

        $payeeFirst = $this->payees->getGatewayAccountId($lineItems[0]);
        $payeesSame = true;

        foreach ($lineItems as $index => $lineItem) {
            if ($index > 0) {
                $payeeCurrent = $this->payees->getGatewayAccountId($lineItem);
                if ($payeeCurrent != $payeeFirst) {
                    $payeesSame = false;
                    break;
                }
            }
        }

        return $payeesSame;
    }

    private function _getStripeExchangeRate($stripeBalanceTransaction, $craftCurrencyCountryCode)
    {
        $exchangeRate = 1;

        $this->log('$stripeBalanceTransaction: ' . strtolower($stripeBalanceTransaction));

        if (
            strtolower($craftCurrencyCountryCode) !== strtolower($stripeBalanceTransaction) &&
            $stripeBalanceTransaction->exchange_rate
        ) {
            // $this->log('Need to convert currency');
            $exchangeRate = $stripeBalanceTransaction->exchange_rate;
        }

        return $exchangeRate;
    }

    // TODO Move to service, ex. ConvertService?
    private function _toStripeAmount($craftPrice, $currencyCountryCode)
    {
        $currency = Commerce::getInstance()->getCurrencies()->getCurrencyByIso($currencyCountryCode);
        
        if (!$currency) {
            throw new NotSupportedException('The currency “' . $currencyCountryCode . '” is not supported!');
        }

        // https://git.io/JGqLi
        // Ex. $50 * (10^2) = 5000
        $amount = $craftPrice * (10 ** $currency->minorUnit);

        $amount = (int) round($amount, 0);

        return $amount;
    }

    private function _fromStripeAmount($amount, $currencyCountryCode)
    {
        $currency = Commerce::getInstance()->getCurrencies()->getCurrencyByIso($currencyCountryCode);

        if (!$currency) {
            throw new NotSupportedException('The currency “' . $currencyCountryCode . '” is not supported!');
        }

        // https://git.io/JGqLi
        // Ex. 5000 / (10^2) = 50
        $craftPrice = $amount / (10 ** $currency->minorUnit);

        return $craftPrice;
    }

    // This logic is also very similar to Button input
    private function _getPayeeFromOrder($order)
    {
        $payeeHandle = self::$plugin->handlesService->getPayeeHandle();
        if ($order && $order['lineItems'] && count($order['lineItems']) >= 1) {
            $firstLineItem = $order['lineItems'][0];
            $product = $firstLineItem['purchasable']['product'];
            if ($payeeHandle && $product) {
                $payeeId = $product[$payeeHandle];
                if ($payeeId) {
                    $payee = User::find()
              ->id($payeeId)
              ->one();

                    if ($payee) {
                        return $payee;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Is Pro.
     *
     * Whether or not this the Pro edition of the plugin is being used.
     *
     * @since 1.4.0
     * @return bool
     */
    private function isPro()
    {
        if (isset($this->EDITION_PRO) && $this->is($this->EDITION_PRO)) {
            return true;
        }

        if (App::env('MARKETPLACE_PRO_BETA')) {
            return true;
        }

        return false;
    }

    /* Adds a quick demo of using template hooks to add the
     * payee to the order editing page. Could use this to try
     * and modify permissions (ex. redirect you away if you aren’t
     * supposed to be able to view this order), but might also might
     * still work better to add a whole new Marketplace tab that shows you
     * the same default order views as Commerce, but filtered down to only
     * show your orders.
     * https://docs.craftcms.com/v3/extend/template-hooks.html */
    private function _reviseOrderTemplate()
    {
        Craft::$app->getView()->hook('cp.commerce.order.edit.main-pane', function (array &$context) {
            $payee = $this->_getPayeeFromOrder($context['order']);
            if ($payee) {
                return Craft::$app->view->renderTemplate(
                    'marketplace/order-edit',
                    [
                  'order' => $context['order'],
                  'payee' => $payee,
              ]
                );
            }
        });

        Craft::$app->getView()->hook('cp.commerce.order.edit.main-pane', function (array &$context) {
            $payee = $this->_getPayeeFromOrder($context['order']);
            if ($payee) {
                return Craft::$app->view->renderTemplate(
                    'marketplace/order-edit-main-pane',
                    [
                  'order' => $context['order'],
                  'payee' => $payee,
              ]
                );
            }
        });
    }

    /**
     * Adds the event handler for registering CP routes.
     */
    private function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'marketplace/fees/new' => 'marketplace/fees/edit',
                'marketplace/fees/<handle:{handle}>' => 'marketplace/fees/edit',

                // Invalid handle – It should be possible to edit them, but not
                // to save them, as they should get validated after that.
                // https://github.com/kennethormandy/craft-marketplace/issues/11
                // TODO Depricate in v2.x
                'marketplace/fees/<handle:[^(?!\s*$)].+>' => 'marketplace/fees/edit',
            ]);
        });
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        $oauthPlugin = OAuthPlugin::$plugin;
        // TODO Might need to only do this if the handle isn’t set
        // (ie. if the settings for the related OAuth app are empty)
        $apps = $oauthPlugin->apps->getAllApps();
        $app = $oauthPlugin->apps->createApp([]);
        $supportedApps = [];

        // TODO Utility function, where we can send all fees
        // and get back only supported fees? ie. one place
        // where the 1st fee gets removed / split into two
        // different arrays. That can be used by the plugin
        // itself, and then also passed the same way to the
        // settings template to render the two different lists
        $fees = self::$plugin->fees->getAllFees();

        foreach ($apps as $key => $app) {
            if (
            $app &&
            $app->provider &&

            // TODO This would check against the list of supported providers
            //      …but right now we are really only supporting this one.
            $app->provider == 'kennethormandy\marketplace\provider\StripeConnectExpressProvider'
          ) {
                $supportedApps[] = $app;
            }
        }

        return Craft::$app->view->renderTemplate(
            'marketplace/settings',
            [
                'settings' => $this->getSettings(),
                'supportedApps' => $supportedApps,
                'app' => $app,
                'fees' => $fees,
                'isPro' => $this->isPro(),
            ]
        );
    }
}
