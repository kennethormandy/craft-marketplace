<?php
namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\commerce\models\LineItem;

use kennethormandy\marketplace\Marketplace;
use kennethormandy\marketplace\events\PayeeEvent;

class PayeesService extends Component
{
    public const EVENT_BEFORE_DETERMINE_PAYEE = 'EVENT_BEFORE_DETERMINE_PAYEE';
    public const EVENT_AFTER_DETERMINE_PAYEE = 'EVENT_AFTER_DETERMINE_PAYEE';
  
    public function init()
    {
        parent::init();
    }
    
    public function getGatewayAccountId(LineItem $lineItem) {
      $purchasable = $lineItem->purchasable;
      $event = new PayeeEvent();
      $event->lineItem = $lineItem;
      $event->gatewayAccountId = null;
      
      if ($this->hasEventHandlers(self::EVENT_BEFORE_DETERMINE_PAYEE)) {
          $this->trigger(self::EVENT_BEFORE_DETERMINE_PAYEE, $event);
      }

      $payeeHandle = Marketplace::$plugin->handlesService->getPayeeHandle();
      if (isset($purchasable[$payeeHandle]) && $purchasable[$payeeHandle] !== null) {
          if (is_numeric($purchasable[$payeeHandle])) {
            // Craft Commerce v3 Digital Products
            $payeeUserId = $purchasable[$payeeHandle];
            $purchasablePayeeUser = User::find()->id($payeeUserId)->one();
          } else {
            // Craft Commerce v2 Digital Products?
            $purchasablePayeeUser = $purchasable[$payeeHandle]->one();
          }
      } elseif (isset($purchasable->product[$payeeHandle]) && $purchasable->product[$payeeHandle] !== null) {
          // All other products
          $payeeUserId = $purchasable->product[$payeeHandle];
          $purchasablePayeeUser = User::find()->id($payeeUserId)->one();
      } else {
          Craft::info(
              '[Marketplace] Stripe ' . $hardCodedApproach . ' no User Payee configured, paying to parent account.',
              __METHOD__
          );

          return;
      }
      
      $stripeConnectHandle = Marketplace::$plugin->handlesService->getButtonHandle($purchasablePayeeUser);
      $payeeStripeAccountId = $purchasablePayeeUser[$stripeConnectHandle];
      
      $event->gatewayAccountId = $payeeStripeAccountId;

      if ($this->hasEventHandlers(self::EVENT_AFTER_DETERMINE_PAYEE)) {
          $this->trigger(self::EVENT_AFTER_DETERMINE_PAYEE, $event);
      }

      // There is another conditional in Marketplace.php rather than
      // here that notifies if $payeeStripeAccountId is missing—
      // should that live in the service instead?

      // We use the event here so an end user can override this
      return $event->gatewayAccountId;
    }
}
