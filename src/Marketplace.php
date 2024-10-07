<?php
/**
 * Marketplace plugin for Craft CMS.
 *
 * Marketplace
 *
 * @link      https://kennethormandy.com
 * @copyright Copyright © 2019–2024 Kenneth Ormandy
 */

namespace kennethormandy\marketplace;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\commerce\elements\Order;
use craft\commerce\events\RefundTransactionEvent;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin as Commerce;
use craft\commerce\services\Payments;
use craft\commerce\stripe\base\Gateway as StripeGateway;
use craft\commerce\stripe\events\BuildGatewayRequestEvent;
use craft\elements\User;
use craft\events\DefineBehaviorsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\UrlHelper;
use craft\services\Fields;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use kennethormandy\marketplace\behaviors\MarketplaceAccount as MarketplaceAccountBehavior;
use kennethormandy\marketplace\fields\MarketplaceConnectButton as MarketplaceConnectButtonField;
use kennethormandy\marketplace\fields\MarketplacePayee as MarketplacePayeeField;
use kennethormandy\marketplace\models\Settings;
use kennethormandy\marketplace\providers\StripeExpressProvider;
use kennethormandy\marketplace\services\Accounts as AccountsService;
use kennethormandy\marketplace\services\Fees as FeesService;
use kennethormandy\marketplace\services\Handles as HandlesService;
use kennethormandy\marketplace\services\Payees as PayeesService;
use kennethormandy\marketplace\variables\MarketplaceVariable;
use Stripe\BalanceTransaction;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Transfer;
use verbb\auth\Auth;
use verbb\auth\base\OAuthProvider;
use verbb\auth\events\AccessTokenEvent;
use verbb\auth\helpers\Session;
use verbb\auth\services\OAuth;
use yii\base\Event;
use yii\base\NotSupportedException;

class Marketplace extends BasePlugin
{
    public const EDITION_LITE = 'lite';
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

    // Can probably be removed, or otherwise to settings if it actually is configurable.

    /**
     * Prefer Separate Transfers
     *
     * Whether or not to prefer that Stripe use “separate charges and transfers,” rather than
     * the “short hand” `application_fee_amount` approach. This is only possible on carts
     * with a single payee, so by default Marketplace prefers separate transfers on all transactions.
     */
    public bool $preferSeparateTransfers = true;

    /**
     * @var Plugin
     */
    public static $plugin;

    /**
     * @inheritDoc
     */
    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            // self::EDITION_PRO,
        ];
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Handle console commands
        if (Craft::$app->request->getIsConsoleRequest()) {
            $this->controllerNamespace = 'kennethormandy\\marketplace\\console\\controllers';
        }

        // Initialize the Auth module
        Auth::registerModule();

        // Register services
        $this->setComponents([
            'handles' => HandlesService::class,
            'fees' => FeesService::class,
            'payees' => PayeesService::class,
            'accounts' => AccountsService::class,
        ]);

        Craft::info('Marketplace plugin loaded', __METHOD__);

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->_defineBehaviors();
            $this->_registerVariables();
            $this->_registerSiteTemplateRoot();
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
                // no break
            case 'warning':
                Craft::warning($msg, 'marketplace');
                break;
            default:
                Craft::info($msg, 'marketplace');
                break;
        }
    }

    /**
     * Set an error using Auth’s flash messages, to provide Marketplace- or Stripe-specific errors.
     * @param $message - The translation string for the message.
     */
    public static function setError(string $message): void
    {
        $message = Craft::t('marketplace', $message);
        Session::setError('marketplace', $message);
        Craft::$app->getUrlManager()->setRouteParams([
            'variables' => ['errorMessage' => $message],
        ]);
    }


    /**
     * Register templates for the MarketplaceVariable Twig helper
     * in a dedicated templates/_site folder.
     */
    private function _registerSiteTemplateRoot(): void
    {
        Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $event) {
            if (is_dir($baseDir = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates/_site')) {
                $event->roots[$this->id] = $baseDir;
            }
        });
    }

    private function _defineBehaviors(): void
    {
        Event::on(Element::class, Element::EVENT_DEFINE_BEHAVIORS, function(DefineBehaviorsEvent $event) {
            $this->_defineBehaviorsAccount($event);
        });
    }

    /**
     * Define the [MarketplaceAccount](../behavior/MarketplaceAccount) behaviour on all elements
     * that have the [MarketplaceConnectButton](../field/MarketplaceConnectButton) field.
     */
    private function _defineBehaviorsAccount(DefineBehaviorsEvent $event): void
    {
        $field = null;
        $fieldLayout = null;
        $accountIdHandle = self::$plugin->handles->getButtonHandle();

        /** @var Element - The element, incl. users, to look for the Marketplace Connect Button field */
        $element = $event->sender;

        if (!$element || !$accountIdHandle) {
            return;
        }

        // Attempt to get a field layout on an element
        try {
            if ($element instanceof \craft\elements\GlobalSet) {

                // We should just be able to do: `$element->getFieldLayout();` but
                // for some reason, the global set behavior is null at this stage.
                $elementFieldLayoutBehavior = $element->getBehavior('fieldLayout');
                if ($elementFieldLayoutBehavior) {
                    $fieldLayout = $elementFieldLayoutBehavior->getFieldLayout();
                }
            } else {

                // Typical case
                $fieldLayout = $element->getFieldLayout();
            }
        } catch (\yii\base\InvalidConfigException) {
        }

        if (!$fieldLayout) {
            return;
        }

        // Attempt to get the field from the field layout
        try {
            $field = $fieldLayout->getFieldByHandle($accountIdHandle);
        } catch (\Exception $e) {
        }

        // It doesn’t have this field, so it shouldn’t get the behavour.
        if (!$field) {
            return;
        }

        // We are namespacing the behaviour here, as there could easily be other “account” behaviours,
        // but this is only really to directly look up the behaviour and not really for end developers.
        $event->behaviors['marketplaceAccount'] = MarketplaceAccountBehavior::class;
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

            // Could provide helper for this, also used in AuthController
            $elementUid = Session::get('elementUid') ?? null;
            Craft::info($elementUid, __METHOD__);

            $marketplaceProviderHandle = StripeExpressProvider::$handle;

            // The element UID provided alongisde the original connection form or URL.
            // TODO Decide whether we still support falling back to the current user? I
            // think that we can, because we restore the session with Auth.
            $originalUserUid = $elementUid;

            $accessTokenJson = $accessToken->jsonSerialize();
            $stripeAccountId = $accessTokenJson['stripe_user_id'] ?? null;
            $providerHandle = Session::get('providerHandle');

            if ($providerHandle === $marketplaceProviderHandle && $stripeAccountId && $originalUserUid) {
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

        // Register our fields
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = MarketplaceConnectButtonField::class;
                $event->types[] = MarketplacePayeeField::class;
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
            function(RefundTransactionEvent $e) {
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
        //         if ($this->isPro() && $this->preferSeparateTransfers) {
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
        //         $payeeStripeAccountId = $this->payees->getAccountId($lineItemOnly);

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

                        /** @var craft\commerce\models\Transaction $purchaseTransaction */
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

                if ($stripeResp->latest_charge) {
                    $stripe = $this->_getStripe();
                    $stripeCharge = $stripe->charges->retrieve($stripeResp->latest_charge);
                }

                // TODO If we don’t have it, get all Stripe charges using:
                // https://docs.stripe.com/api/charges/list
                // Providing the payment intent

                // // Get the first captured transaction
                // if (
                //     isset($stripeResp->charges) && $stripeResp->charges &&
                //     isset($stripeResp->charges->data) && $stripeResp->charges->data &&
                //     count($stripeResp->charges->data) >= 1
                // ) {
                //     foreach ($stripeResp->charges->data as $charge) {
                //         $this->log(json_encode($charge));
                //         if ($charge && $charge->captured && $charge->status === 'succeeded') {
                //             $stripeCharge = $charge;
                //             break;
                //         }
                //     }
                // }

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
                    $payeeCurrent = $this->payees->getAccountId($lineItem);

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
                        'source_transaction' => $stripeCharge->id,
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

    private function _getStripe(): StripeClient
    {
        $stripeSecretKey = $this->getSettings()->getSecretApiKey();
        $stripe = new StripeClient($stripeSecretKey);

        return $stripe;
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
     */
    private function isPro(): bool
    {
        if (isset($this->EDITION_PRO) && $this->is($this->EDITION_PRO)) {
            return true;
        }

        return false;
    }

    /**
     * Get the Stripe Express OAuth provider.
     *
     * @since 2.0.0
     */
    public function getProvider(): OAuthProvider
    {
        $settings = $this->getSettings();

        $provider = new StripeExpressProvider([
            'clientId' => $settings->clientId,
            'clientSecret' => $settings->secretApiKey,

            // The redirectUri pointing to our own `actionCallback` method
            'redirectUri' => UrlHelper::actionUrl('marketplace/auth/callback', null, null, false),
        ]);

        return $provider;
    }

    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, static function(Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('marketplace', MarketplaceVariable::class);
        });
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     */
    protected function createSettingsModel(): ?Model
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
        $settings = $this->getSettings();

        return Craft::$app->view->renderTemplate(
            'marketplace/settings',
            [
                'settings' => $settings,
                'isPro' => $this->isPro(),
                'provider' => $this->getProvider(),
            ]
        );
    }
}
