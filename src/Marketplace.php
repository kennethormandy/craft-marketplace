<?php
/**
 * Marketplace plugin for Craft CMS 3.x
 *
 * Marketplace
 *
 * @link      https://kennethormandy.com
 * @copyright Copyright © 2019–2020 Kenneth Ormandy
 */

namespace kennethormandy\marketplace;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\services\Fields;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use craft\elements\User;

use craft\commerce\models\Transaction;
use craft\commerce\stripe\events\BuildGatewayRequestEvent;
use craft\commerce\stripe\base\Gateway as StripeGateway;
use craft\commerce\events\RefundTransactionEvent;
use craft\commerce\services\Payments;
use yii\base\Event;

use venveo\oauthclient\services\Providers;
use venveo\oauthclient\services\Apps as AppsService;
use venveo\oauthclient\events\AuthorizationUrlEvent;

use venveo\oauthclient\base\Provider;
use venveo\oauthclient\services\Tokens;
use venveo\oauthclient\events\TokenEvent;

use kennethormandy\marketplace\provider\StripeConnectProvider;
use kennethormandy\marketplace\provider\StripeConnectExpressProvider;
use kennethormandy\marketplace\fields\MarketplaceConnectButton as MarketplaceConnectButtonField;
use kennethormandy\marketplace\fields\MarketplacePayee as MarketplacePayeeField;
use kennethormandy\marketplace\controllers\AccountController;
use kennethormandy\marketplace\services\HandlesService;
use kennethormandy\marketplace\models\Settings;

use Stripe\Stripe;
use Stripe\Transfer;

class Marketplace extends BasePlugin
{
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

    public function init()
    {
        parent::init();
        self::$plugin = $this;
        
        $this->_registerCpRoutes();
        
        // Register services
        $this->setComponents([
            'handlesService' => HandlesService::class,
        ]);
        
        Craft::info(
            'Marketplace plugin loaded',
            __METHOD__
        );
        
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

                Craft::info('EVENT_CREATE_TOKEN_MODEL_FROM_RESPONSE', __METHOD__);
                Craft::info(json_encode($stripeResponse), __METHOD__);

                if (isset($stripeResponse)) {
                    Craft::info('Stripe response', __METHOD__);
                    Craft::info(json_encode($stripeResponse), __METHOD__);
                
                    if (
                  isset($stripeResponse) &&
                  isset($stripeResponse->stripe_user_id)
                ) {
                        Craft::info('Stripe Account Id stripe_user_id', __METHOD__);
                        $stripeAccountId = $stripeResponse->stripe_user_id;

                        // TODO Might be possible to pass along original user ID to Stripe,
                        // and then get it back in the resp, otherwise hypothetically you can
                        // be logged in as a different user to initiate the process, and have the
                        // result applied to the wrong user.
                        $userId = Craft::$app->user->getId();
                        $userObject = Craft::$app->users->getUserById($userId);

                        // Need to get the field handle for this, in the same
                        // way that we do for the payee
                        // TODO This is only working because we fall back to 'stripeConnect'
                        $stripeConnectHandle = $this->handlesService->getButtonHandle($userObject);

                        // …this is returning nothing, not actually getting the $stripeConnectHandle
                        Craft::info(
                            'Got Marketplace handle ' . $stripeConnectHandle,
                            __METHOD__
                        );

                        // TODO THis is
                        $stripeConnectHandle = $stripeConnectHandle ? $stripeConnectHandle : 'stripeConnect';
                        $userObject->setFieldValue($stripeConnectHandle, $stripeAccountId);

                        Craft::$app->elements->saveElement($userObject);
                    }
                }
            }
        );
                        
        // Handle Stripe redirect back to Craft
        // Haven’t really tested this yet
        Event::on(
            AppsService::class,
            AppsService::EVENT_GET_URL_OPTIONS,
            function (AuthorizationUrlEvent $e) {
                Craft::info('EVENT_GET_URL_OPTIONS', __METHOD__);
                Craft::info(json_encode($e), __METHOD__);

                if ($e->app->handle === 'stripe') {
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

        /**
        * Logging in Craft involves using one of the following methods:
        *
        * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
        * Craft::info(): record a message that conveys some useful information.
        * Craft::warning(): record a warning message that indicates something unexpected has happened.
        * Craft::error(): record a fatal error that should be investigated as soon as possible.
        *
        * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
        *
        * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
        * the category to the method (prefixed with the fully qualified class name) where the constant appears.
        *
        * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
        * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
        *
        * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
        */
        
        // Could provide more options around this, ex. who is responsible for
        // the refund, the platform or connected account?
        // Refund the application fee too? Etc.
        // TODO Actually want to do this after, but trying to block it right now
        Event::on(
            Payments::class,
            Payments::EVENT_BEFORE_REFUND_TRANSACTION,
            function (RefundTransactionEvent $e) {
                Craft::info('EVENT_BEFORE_REFUND_TRANSACTION', __METHOD__);

                // We are assuming all of these are destination charges,
                // might need to find some way to look at the charge and
                // see if we can tell which one it was (no good checking the
                // settings, because it could have changed since the order
                // was made).
            
                $secretApiKey = Marketplace::$plugin->getSettings()->getSecretApiKey();
                Stripe::setApiKey($secretApiKey);

                if (isset($e->transaction->response)) {
                    $res = json_decode($e->transaction->response);

                    // In progress:
                    Craft::info(
                        '[Stripe refund] ' . $e->transaction->response,
                        __METHOD__
                    );

                    if (isset($res->charges) && isset($res->charges->data) && count($res->charges->data) >= 1) {
                        $originalCharge = $res->charges->data[0];

                        if (isset($originalCharge->transfer)) {
                            $transferId = $originalCharge->transfer;

                            // TODO This should be the filled in refund amount, otherwise this
                            $refundAmount = (int)$originalCharge->amount;

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
                // TODO Temporary hard-coded config
                // Not supporting direct charges for now, looks like it
                // would require a change to Craft Commerce Stripe gateway
                // $hardCodedApproach = 'direct-charge';
                $hardCodedApproach = 'destination-charge';
        
                // Must be a positive integer (in cents)
                $hardCodedApplicationFee = 0;
        
                // Make Destination Charges behave more like Direct Charges
                // https://stripe.com/docs/payments/connected-accounts#charge-on-behalf-of-a-connected-account
                $hardCodedOnBehalfOf = false;
        
                if ($e->transaction->type === 'purchase') {
                    $order = $e->transaction->order;
        
                    // Don’t use the plugin if we have more than one line item
                    // TODO Instead, maybe we should not use the plugin if we have
                    //      two or more payees that differ between line items
                    if (!($order && $order->lineItems && sizeof($order->lineItems) >= 1)) {
                        return;
                    }
        
                    // Only supports one line item right now,
                    // otherwise we’d probably need different
                    // Stripe transfer approach
                    $lineItemOnly = $order->lineItems[0];
        
                    $purchasable = $lineItemOnly->purchasable;
                    $payeeHandle = $this->handlesService->getPayeeHandle();
                    if (isset($purchasable[$payeeHandle])) {
                        // TODO Digital Products?
                        $purchasablePayee = $purchasable[$payeeHandle]->one();
                    } elseif (isset($purchasable->product[$payeeHandle])) {
                        // All other products
                        $payeeId = $purchasable->product[$payeeHandle];
                        $purchasablePayee = User::find()->id($payeeId)->one();
                    } else {
                        Craft::info(
                            'Stripe ' . $hardCodedApproach . ' no User Payee configured, paying to parent account.',
                            __METHOD__
                        );
        
                        return;
                    }
        
                    $stripeConnectHandle = $this->handlesService->getButtonHandle($purchasablePayee);
                    $payeeStripeAccountId = $purchasablePayee[$stripeConnectHandle];
        
                    if (!$payeeStripeAccountId) {
                        Craft::info(
                            'Stripe ' . $hardCodedApproach . ' no User Payee Account ID, paying to parent account.',
                            __METHOD__
                        );
        
                        return;
                    }
        
                    Craft::info(
                        'Stripe ' . $hardCodedApproach . ' to Stripe Account ID: ' . $payeeStripeAccountId,
                        __METHOD__
                    );
        
                    if ($hardCodedApproach === 'destination-charge') {
                        Craft::info(
                            '[Marketplace request] [' . $hardCodedApproach . '] ' . json_encode($e->request),
                            __METHOD__
                        );
        
                        Craft::info(
                            '[Marketplace request] [' . $hardCodedApproach . '] destination ' . $payeeStripeAccountId,
                            __METHOD__
                        );
        
                        // Apply application fee, if it’s a positive int
                        if ($hardCodedApplicationFee && is_int($hardCodedApplicationFee) && $hardCodedApplicationFee > 0) {
                            $e->request['application_fee_amount'] = $hardCodedApplicationFee;
                        }
        
                        if ($hardCodedOnBehalfOf) {
                            $e->request['on_behalf_of'] = $payeeStripeAccountId;
                        }
        
                        // https://stripe.com/docs/connect/quickstart#process-payment
                        // https://stripe.com/docs/connect/destination-charges
                        $e->request['transfer_data'] = [
                  "destination" => $payeeStripeAccountId
                ];
        
                        Craft::info(
                            '[Marketplace request modified] [' . $hardCodedApproach . '] ' . json_encode($e->request),
                            __METHOD__
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
            }
        );
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
      Craft::$app->getView()->hook('cp.commerce.order.edit.main-pane', function(array &$context) {

        // This is a demo using the same logic already in
        // MarketplaceConnectButton_input. Would want to turn this
        // into Twig if we actually go ahead with using it.
        $payeeHandle = Marketplace::$plugin->handlesService->getPayeeHandle();
        $order = $context['order'];
        if ($order && $order['lineItems'] && sizeof($order['lineItems']) >= 1) {
          $firstLineItem = $order['lineItems'][0];
          $product = $firstLineItem['purchasable']['product'];
          if ($product) {
            $payeeId = $product[$payeeHandle];
            if ($payeeId) {
              $payee = \craft\elements\User::find()
                ->id($payeeId)
                ->one();
              
              if ($payee) {
                return '<strong>Payee</strong> ' . $payee;                
              }
            }
          }
        }
      });
    }
    
    /**
     * Adds the event handler for registering CP routes
     */
    private function _registerCpRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules = array_merge($event->rules, [
            'marketplace/account/create-login-link' => 'marketplace/account/create-login-link',
          ]);
            }
        );
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
        return Craft::$app->view->renderTemplate(
            'marketplace/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

}
