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
use craft\services\Fields;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use craft\elements\User;
use craft\helpers\UrlHelper;

use craft\commerce\stripe\events\BuildGatewayRequestEvent;
use craft\commerce\stripe\base\Gateway as StripeGateway;
use craft\commerce\events\RefundTransactionEvent;
use craft\commerce\services\Payments;
use yii\base\Event;

use venveo\oauthclient\Plugin as OAuthPlugin;
use venveo\oauthclient\services\Providers;
use venveo\oauthclient\services\Apps as AppsService;
use venveo\oauthclient\events\AuthorizationUrlEvent;
use venveo\oauthclient\events\AuthorizationEvent;
use venveo\oauthclient\controllers\AuthorizeController;
use venveo\oauthclient\base\Provider;
use venveo\oauthclient\events\TokenEvent;
use putyourlightson\logtofile\LogToFile;

use kennethormandy\marketplace\provider\StripeConnectProvider;
use kennethormandy\marketplace\provider\StripeConnectExpressProvider;
use kennethormandy\marketplace\fields\MarketplaceConnectButton as MarketplaceConnectButtonField;
use kennethormandy\marketplace\fields\MarketplacePayee as MarketplacePayeeField;
use kennethormandy\marketplace\controllers\AccountController;
use kennethormandy\marketplace\controllers\FeesController;
use kennethormandy\marketplace\services\HandlesService;
use kennethormandy\marketplace\services\FeesService;
use kennethormandy\marketplace\services\PayeesService;
use kennethormandy\marketplace\models\Settings;

use Stripe\Stripe;
use Stripe\Transfer;

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

                LogToFile::info('EVENT_CREATE_TOKEN_MODEL_FROM_RESPONSE', 'marketplace');
                LogToFile::info(json_encode($stripeResponse), 'marketplace');

                if (isset($stripeResponse)) {
                    LogToFile::info('Stripe response', 'marketplace');
                    LogToFile::info(json_encode($stripeResponse), 'marketplace');
                
                    if (
                  isset($stripeResponse) &&
                  isset($stripeResponse->stripe_user_id)
                ) {
                        LogToFile::info('Stripe Account Id stripe_user_id', 'marketplace');
                        $stripeAccountId = $stripeResponse->stripe_user_id;

                        // TODO Might be possible to pass along original user ID to Stripe,
                        // and then get it back in the resp, otherwise hypothetically you can
                        // be logged in as a different user to initiate the process, and have the
                        // result applied to the wrong user.
                        $userId = Craft::$app->user->getId();
                        $userObject = Craft::$app->users->getUserById($userId);

                        $stripeConnectHandle = $this->handlesService->getButtonHandle($userObject);

                        LogToFile::info(
                            'Got Marketplace handle ' . $stripeConnectHandle,
                            'marketplace'
                        );

                        // TODO Instead of using a fallback, we should probably
                        //      show a warning that we don’t have a the correct
                        //      handle, or that there is no button.
                        $stripeConnectHandle = $stripeConnectHandle ? $stripeConnectHandle : 'stripeConnect';
                        $userObject->setFieldValue($stripeConnectHandle, $stripeAccountId);

                        Craft::$app->elements->saveElement($userObject);
                    }
                }
            }
        );

        Event::on(
            AppsService::class,
            AppsService::EVENT_GET_URL_OPTIONS,
            function (AuthorizationUrlEvent $e) {
                LogToFile::info('EVENT_GET_URL_OPTIONS', 'marketplace');
                LogToFile::info(json_encode($e), 'marketplace');
                $appHandle = Marketplace::$plugin->getSettings()->getAppHandle();

                LogToFile::info('Get App handle '. $appHandle . ' '. $e->app->handle, 'marketplace');

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
                        
                        // TODO Handle Stripe redirect back to Craft
                    }
                }
            
                return $e;
            }
        );
        
        Event::on(
          AuthorizeController::class,
          AuthorizeController::EVENT_BEFORE_AUTHENTICATE,
          function (AuthorizationEvent $event)
          {
              // LogToFile::info('EVENT_BEFORE_AUTHENTICATE', 'marketplace');
              if (
                $event->context &&
                isset($event->context['location']) &&
                isset($event->context['location']['pathname']) &&
                $event->context['contextName'] === 'MarketplaceConnectButton') {
                  $loc = $event->context['location'];
                  $pathname = $loc['pathname'];
                  if ($loc['hash']) {
                    $pathname = $pathname . $loc['hash'];
                  }

                  $returnUrl = UrlHelper::cpUrl($pathname, null, null);
                  LogToFile::info('Return URL', 'marketplace');
                  LogToFile::info($returnUrl, 'marketplace');
                  $event->returnUrl = $returnUrl;
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
                LogToFile::info('EVENT_BEFORE_REFUND_TRANSACTION', 'marketplace');

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
                    LogToFile::info('[Stripe refund] ' . $e->transaction->response, 'marketplace');

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
        
                $applicationFees = Marketplace::$plugin->fees->getAllFees();
        
                // Make Destination Charges behave more like Direct Charges
                // https://stripe.com/docs/payments/connected-accounts#charge-on-behalf-of-a-connected-account
                $hardCodedOnBehalfOf = false;
        
                if ($e->transaction->type === 'purchase') {
                    $order = $e->transaction->order;

                    if (!$order || !$order->lineItems || sizeof($order->lineItems) == 0) {
                      return;
                    }
                    
                    // By default only supports one line item, to match Commerce Lite
                    $lineItemOnly = $order->lineItems[0];
                    $payeeStripeAccountId = $this->payees->getGatewayAccountId($lineItemOnly);

                    if (!$payeeStripeAccountId) {
                        LogToFile::info(
                            '[Order #' . $order->id . '] Stripe ' . $hardCodedApproach . ' no User Payee Account ID. Paying to parent account.',
                            'marketplace'
                        );
        
                        return;
                    }

                    // If there’s more than one line item, we check they all have the
                    // same payees, and allow the payment splitting as long as
                    // they all match.
                    if (sizeof($order->lineItems) > 1) {
                      // Iterate over line items, and get payees
                      // If one payee is different from all others, return
                      // Maybe we don’t actually need a setting then: you are just gaining
                      // a new feature if try and run through multiple line items with
                      // Commerce Pro AND they are all the same payee. Otherwise, if they are
                      // different payees, you’ll continue to get the same behvaiour: the plugin
                      // won’t be used.
                      
                      $payeesSame = true;
                      $lineItemPayees = [];
                      foreach ($order->lineItems as $key => $lineItem) {
                        if ($key > 0) {
                          $payeeCurrent = $this->payees->getGatewayAccountId($lineItem);
                          if ($payeeCurrent != $payeeStripeAccountId) {
                            $payeesSame = false;
                            return;
                          }
                        }
                      }

                      if ($payeesSame == false) {
                        LogToFile::info(
                            'Stripe ' . $hardCodedApproach . ' line items have different User Payee Account IDs. Paying to parent account.',
                            'marketplace'
                        );


                        return;
                      }
                    }
        
                    LogToFile::info(
                        'Stripe ' . $hardCodedApproach . ' to Stripe Account ID: ' . $payeeStripeAccountId,
                        'marketplace'
                    );
        
                    if ($hardCodedApproach === 'destination-charge') {
                        LogToFile::info(
                            '[Marketplace request] [' . $hardCodedApproach . '] ' . json_encode($e->request),
                            'marketplace'
                        );
        
                        LogToFile::info(
                            '[Marketplace request] [' . $hardCodedApproach . '] destination ' . $payeeStripeAccountId,
                            'marketplace'
                        );
        
                        // Apply application fee, if it’s a positive int
                        if ($applicationFees && sizeof($applicationFees) >= 1) {

                            $feeCounter = 0;
                            foreach ($applicationFees as $feeId => $fee) {
                              // TODO Only supporting 1 fee for Lite edition
                              if ($feeCounter === 0) {
                                  $liteApplicationFee = $fee;
                              }

                              $feeCounter++;
                            }
                            
                            if ($liteApplicationFee && (int)$liteApplicationFee->value > 0) {
                                $liteApplicationFeeAmount = 0;

                                if ($liteApplicationFee->type === 'price-percentage') {
                                    // Ex. 12.50% fee stored in DB as 1250
                                    $percent = ($liteApplicationFee->value / 100);

                                    // $10 subtotal * 12.5 = 125 cent application fee
                                    $liteApplicationFeeAmount = (int)$order->itemSubtotal * $percent;
                                } else if ($liteApplicationFee->type === 'flat-fee') {

                                    // Ex. $10 fee stored in DB as 1000 = 1000 cent fee
                                    $liteApplicationFeeAmount = (int)$liteApplicationFee->value;
                                }
                                                                
                                // Must be a positive integer (in cents)
                                if ($liteApplicationFeeAmount > 0 && is_int($liteApplicationFeeAmount)) {
                                    $e->request['application_fee_amount'] = $liteApplicationFeeAmount;
                                }
                            }
                        }
        
                        if ($hardCodedOnBehalfOf) {
                            $e->request['on_behalf_of'] = $payeeStripeAccountId;
                        }
        
                        // https://stripe.com/docs/connect/quickstart#process-payment
                        // https://stripe.com/docs/connect/destination-charges
                        $e->request['transfer_data'] = [
                  "destination" => $payeeStripeAccountId
                ];
        
                        LogToFile::info(
                            '[Marketplace request modified] [' . $hardCodedApproach . '] ' . json_encode($e->request),
                            'marketplace'
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
    
    // This logic is also very similar to Button input
    private function _getPayeeFromOrder($order)
    {
      $payeeHandle = Marketplace::$plugin->handlesService->getPayeeHandle();
      if ($order && $order['lineItems'] && sizeof($order['lineItems']) >= 1) {
        $firstLineItem = $order['lineItems'][0];
        $product = $firstLineItem['purchasable']['product'];
        if ($product) {
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
        $payee = $this->_getPayeeFromOrder($context['order']);
        if ($payee) {
          return Craft::$app->view->renderTemplate(
              'marketplace/order-edit',
              [
                  'order' => $context['order'],
                  'payee' => $payee
              ]
          );          
        }
      });
      
      Craft::$app->getView()->hook('cp.commerce.order.edit.main-pane', function(array &$context) {
        $payee = $this->_getPayeeFromOrder($context['order']);
        if ($payee) {
          return Craft::$app->view->renderTemplate(
              'marketplace/order-edit-main-pane',
              [
                  'order' => $context['order'],
                  'payee' => $payee
              ]
          );
        }
      });
    }
    
    /**
     * Adds the event handler for registering CP routes
     */
    private function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'marketplace/account/create-login-link' => 'marketplace/account/create-login-link',
                'marketplace/fees/new' => 'marketplace/fees/edit',
                'marketplace/fees/<handle:{handle}>' => 'marketplace/fees/edit',

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
        $fees = Marketplace::$plugin->fees->getAllFees();

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
                'fees' => $fees
            ]
        );
    }

}
