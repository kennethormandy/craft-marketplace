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
use craft\elements\User;
use craft\errors\InvalidFieldException;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\services\Fields;
use craft\web\UrlManager;
use craft\web\View;
use kennethormandy\marketplace\fields\MarketplaceConnectButton as MarketplaceConnectButtonField;
use kennethormandy\marketplace\fields\MarketplacePayee as MarketplacePayeeField;
use kennethormandy\marketplace\models\Settings;
use kennethormandy\marketplace\providers\StripeExpressProvider;
use kennethormandy\marketplace\services\Accounts as AccountsService;
use kennethormandy\marketplace\services\Fees as FeesService;
use kennethormandy\marketplace\services\Handles as HandlesService;
use kennethormandy\marketplace\services\Payees as PayeesService;
use Stripe\Stripe;
use Stripe\Transfer;
use Stripe\BalanceTransaction;
use verbb\auth\events\AccessTokenEvent;
// use verbb\auth\events\AuthorizationUrlEvent;
use verbb\auth\helpers\Session;
use verbb\auth\services\OAuth;
use verbb\sociallogin\services\Providers;
use yii\base\Event;

class Marketplace extends BasePlugin
{
    const EDITION_LITE = 'lite';
    // NOTE Edition marker doesn’t show, unless there
    //      is more than one edition. https://git.io/JvAIY
    // const EDITION_PRO = 'pro';

    /**
     * @inheritDoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritDoc
     */
    public string $schemaVersion = '0.1.0';

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

        // Register services
        $this->setComponents([
            'handles' => HandlesService::class,
            'fees' => FeesService::class,
            'payees' => PayeesService::class,
            'accounts' => AccountsService::class,
        ]);

        Craft::info('Marketplace plugin loaded', __METHOD__);

        // This has to run before `onInit` for some reason
        Event::on(
            Providers::class,
            Providers::EVENT_REGISTER_PROVIDER_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = StripeExpressProvider::class;
            }
        );

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->_attachEventHandlers();
        });
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
        switch ($level) {
            case 'error':
                Craft::error($msg, 'marketplace');
            case 'warning':
                Craft::warning($msg, 'marketplace');
                break;
            default:
                Craft::info($msg, 'marketplace');
                break;
        }
    }

    private function _attachEventHandlers(): void
    {

        Event::on(OAuth::class, OAuth::EVENT_AFTER_FETCH_ACCESS_TOKEN, function(AccessTokenEvent $event) {
            $provider = $event->provider;
            $ownerHandle = $event->ownerHandle;
            $accessToken = $event->accessToken;
            $token = $event->token;

            Craft::info('EVENT_AFTER_FETCH_ACCESS_TOKEN', __METHOD__);

            Craft::info(json_encode($event), __METHOD__);
            Craft::info(json_encode($provider), __METHOD__);
            Craft::info(json_encode($ownerHandle), __METHOD__);
            Craft::info($accessToken, __METHOD__);
            Craft::info($accessToken->getValues(), __METHOD__);
            Craft::info($accessToken->jsonSerialize(), __METHOD__);
            Craft::info(json_encode($token), __METHOD__);
            Craft::info('Session::get("data")', __METHOD__);
            Craft::info(Session::get('data'), __METHOD__);

            // This might need to be configurable, too, or the plugin would
            // come with an explicitly namespaced `marketplaceStripeExpress` provider.
            $marketplaceProviderHandle = StripeExpressProvider::$handle;

            // The data provided alongisde the original connection form or URL
            $data = Session::get('data');

            // TODO Decide whether we still support falling back to the current user? I
            // think that we can, because Social Login checks that the session is the same.
            $originalUserUid = $data['elementUid'] ?? null;

            $accessTokenJson = $accessToken->jsonSerialize();
            $stripeAccountId = $accessTokenJson['stripe_user_id'] ?? null;
            $providerHandle = Session::get('providerHandle');

            if ($providerHandle === $marketplaceProviderHandle && $stripeAccountId && $originalUserUid) {
                $elementUid = $data['elementUid'];
                $elementType = null; // Could support passing this along to speed up the query
                $element = Craft::$app->elements->getElementByUid($elementUid, $elementType);

                if ($element) {

                    // The field handle for the Marketplace Connect Button
                    $fieldHandle = $this->handles->getButtonHandle($element);

                    Craft::info('$element', __METHOD__);
                    Craft::info($element, __METHOD__);
                    
                    $element->setFieldValue($fieldHandle, $stripeAccountId);
                    Craft::$app->elements->saveElement($element);

                }
            }

        });


        $this->_reviseOrderTemplate();

        // Register our fields
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = MarketplaceConnectButtonField::class;
                $event->types[] = MarketplacePayeeField::class;
            }
        );

        // Event::on(
        //     AppsService::class,
        //     AppsService::EVENT_GET_URL_OPTIONS,
        //     function (AuthorizationUrlEvent $e) {
        //         $this->log('EVENT_GET_URL_OPTIONS');
        //         $this->log(json_encode($e));
        //         $appHandle = self::$plugin->getSettings()->getAppHandle();

        //         $this->log('Get App handle ' . $appHandle . ' ' . $e->app->handle);

        //         // TODO We want to check the handle matches, and the type
        //         // of provider is our Stripe provider, as you could in theory
        //         // have an app wit hthe handle `stripe` that matches that is
        //         // actually the Google provider (or whatever)
        //         if ($e->app->handle === $appHandle) {
        //             if ($e->context && isset($e->context['user'])) {
        //                 $user = $e->context['user'];
        //                 $userId = $user['id'];

        //                 // Set user id that oauth plugin will use to the
        //                 // profile page’s userId, rather than the logged in
        //                 // person’s userId
        //                 // This didn’t actually work
        //                 // $e->app->userId = $userId;

        //                 // So we have this when the request comes back
        //                 $e->options['craft_user_id'] = $userId;
        //                 $e->options['craft_user_uid'] = $user['uid'];

        //                 // Add other options to the base url, ex. existing profile into to prefill
        //                 // https://stripe.com/docs/connect/express-accounts#prefill-form-fields
        //                 if (isset($user['email'])) {
        //                     $e->options['stripe_user[email]'] = $user['email'];
        //                 }

        //                 if (isset($user['url'])) {
        //                     $e->options['stripe_user[url]'] = $user['url'];
        //                 }

        //                 if (isset($user['firstName'])) {
        //                     $e->options['stripe_user[first_name]'] = $user['firstName'];
        //                 }

        //                 if (isset($user['lastName'])) {
        //                     $e->options['stripe_user[last_name]'] = $user['lastName'];
        //                 }
        //             }
        //         }

        //         return $e;
        //     }
        // );

        // Event::on(
        //     AuthorizeController::class,
        //     AuthorizeController::EVENT_BEFORE_AUTHENTICATE,
        //     function (AuthorizationEvent $event) {

        //         if ($event->context) {
        //             $this->log('EVENT_BEFORE_AUTHENTICATE context');
        //             $this->log(json_encode($event->context));    
        //         }

        //         if (
        //         $event->context &&
        //         isset($event->context['location']) &&
        //         isset($event->context['location']['pathname']) &&
        //         $event->context['contextName'] === 'MarketplaceConnectButton') {
        //             $loc = $event->context['location'];
        //             $pathname = $loc['pathname'];
        //             if (isset($loc['hash'])) {
        //                 $pathname = $pathname . $loc['hash'];
        //             }

        //             $returnUrl = UrlHelper::cpUrl($pathname, null, null);
        //             $this->log('Return URL');
        //             $this->log($returnUrl);
        //             $event->returnUrl = $returnUrl;
        //         }
        //     }
        // );

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

        // Event::on(
        //     StripeGateway::class,
        //     StripeGateway::EVENT_BUILD_GATEWAY_REQUEST,
        //     function (BuildGatewayRequestEvent $e) {
        //         if ($this->isPro() && $this->getSettings()->stripePreferSeparateTransfers) {
        //             return;
        //         }

        //         $applicationFeeAmount = 0;

        //         // Make Destination Charges behave more like Direct Charges
        //         // https://stripe.com/docs/payments/connected-accounts#charge-on-behalf-of-a-connected-account
        //         $hardCodedOnBehalfOf = false;

        //         if ($e->transaction->type !== 'purchase' && $e->transaction->type !== 'authorize') {
        //             $this->log(
        //                 'Unsupported transaction type: ' . $e->transaction->type
        //             );

        //             return;
        //         }


        //         $order = $e->transaction->order;

        //         if (!$order || !$order->lineItems || count($order->lineItems) == 0) {
        //             return;
        //         }

        //         // By default only supports one line item, to match Commerce Lite
        //         $lineItemOnly = $order->lineItems[0];
        //         $payeeStripeAccountId = $this->payees->getGatewayAccountId($lineItemOnly);

        //         if (!$payeeStripeAccountId) {
        //             $this->log(
        //                 '[Order #' . $order->id . '] Stripe no User Payee Account ID. Paying to parent account.'
        //             );

        //             return;
        //         }

        //         // If there’s more than one line item, we check they all have the
        //         // same payees. In Lite, we’ll allow the payment splitting as long as
        //         // they all match. In Pro, we’ll split payments.
        //         if (count($order->lineItems) > 1) {
        //             $payeesSame = $this->_checkAllPayeesSame($order);

        //             if (!$payeesSame) {
        //                 // If it’s the Lite edition, but payees are not the same,
        //                 // we don’t support this scenario. Instead, we return,
        //                 // which means the transaction will go through as a
        //                 // normal Craft Commerce transaction.
        //                 if (!$this->isPro()) {
        //                     $this->log(
        //                         'Stripe line items have different User Payee Account IDs. Paying to parent account.'
        //                     );
        //                 }

        //                 // If it’s the Pro edition, the remainder is handled after payment.
        //                 return;
        //             }
        //         }

        //         $this->log(
        //             'Stripe to Stripe Account ID: ' . $payeeStripeAccountId
        //         );

        //         $this->log(
        //             '[Marketplace request]' . json_encode($e->request)
        //         );

        //         $this->log(
        //             '[Marketplace request] destination ' . $payeeStripeAccountId
        //         );

        //         // Fees are always based on lineItems
        //         foreach ($order->lineItems as $lineItemId => $lineItem) {
        //             $lineItemFeeAmount = $this->fees->calculateFeesAmount($lineItem, $order);
        //             $applicationFeeAmount = $applicationFeeAmount + $lineItemFeeAmount;
        //         }

        //         if ($applicationFeeAmount) {
        //             $stripeApplicationFeeAmount = $this->_toStripeAmount($applicationFeeAmount, $order->paymentCurrency->iso);
        //             $e->request['application_fee_amount'] = $stripeApplicationFeeAmount;
        //         }

        //         if ($hardCodedOnBehalfOf) {
        //             $e->request['on_behalf_of'] = $payeeStripeAccountId;
        //         }

        //         // https://stripe.com/docs/connect/quickstart#process-payment
        //         // https://stripe.com/docs/connect/destination-charges
        //         $e->request['transfer_data'] = [
        //             'destination' => $payeeStripeAccountId,
        //         ];

        //         $this->log(
        //             '[Marketplace request modified] ' . json_encode($e->request)
        //         );
        //     }
        // );

        Event::on(
            Order::class,
            Order::EVENT_AFTER_COMPLETE_ORDER,
            function(Event $event) {
                /** @var Order $order */
                $order = $event->sender;
                $purchaseTransaction = null;

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

                foreach ($order->lineItems as $key => $lineItem) {
                    $payeeCurrent = $this->payees->getGatewayAccountId($lineItem);

                    $this->log('lineItem');
                    $this->log(json_encode($lineItem));

                    // If there isn’t a payee or a total on this line item, nothing to do
                    if (!$payeeCurrent || $lineItem->total === (float) 0) {
                        continue;
                    }

                    $this->log('Craft amount before currency conversion: ' . $lineItem->total);

                    $lineItemTotal = $lineItem->total;

                    // Calculate LineItem fee
                    $feeAmountLineItem = $this->fees->calculateFeesAmount($lineItem, $order);

                    if ($feeAmountLineItem) {
                        $lineItemTotal = $lineItemTotal - $feeAmountLineItem;
                    }

                    // Don’t touch the subtotal, unless we really to have to
                    if ($exchangeRate && $exchangeRate !== 1) {
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

                    try {
                        $transferResult = Transfer::create($stripeTransferData);
                        $this->log('Transfer Result');
                        $this->log(json_encode($transferResult));    
                    } catch (\Exception $e) {
                        $this->log('Marketplace transfer error', [], 'error');
                        $this->log($e->getTraceAsString(), [], 'error');
                    }
                }
            }
        );

    }

    private function _getStripeExchangeRate($stripeBalanceTransaction, $craftCurrencyCountryCode)
    {
        $exchangeRate = 1;

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
        $payeeHandle = self::$plugin->handles->getPayeeHandle();
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
     * Whether or not the Pro edition of this plugin is being used.
     *
     * @since 1.4.0
     * @return bool
     */
    private function isPro()
    {
        if (isset($this->EDITION_PRO) && $this->is($this->EDITION_PRO)) {
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

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel(): ?\craft\base\Model
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
        // $oauthPlugin = OAuthPlugin::$plugin;
        // // TODO Might need to only do this if the handle isn’t set
        // // (ie. if the settings for the related OAuth app are empty)
        // $apps = $oauthPlugin->apps->getAllApps();
        // $app = $oauthPlugin->apps->createApp([]);
        $supportedApps = [];

        // foreach ($apps as $key => $app) {
        //     if (
        //     $app &&
        //     $app->provider &&

        //     // TODO This would check against the list of supported providers
        //     //      …but right now we are really only supporting this one.
        //     $app->provider == 'kennethormandy\marketplace\provider\StripeConnectExpressProvider'
        //   ) {
        //         $supportedApps[] = $app;
        //     }
        // }

        return Craft::$app->view->renderTemplate(
            'marketplace/settings',
            [
                'settings' => $this->getSettings(),
                'supportedApps' => $supportedApps,
                // 'app' => $app,
                'isPro' => $this->isPro(),
                'providerHandle' => StripeExpressProvider::$handle,
            ]
        );
    }
}
