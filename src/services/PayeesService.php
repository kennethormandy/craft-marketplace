<?php
namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\commerce\elements\Order;

use kennethormandy\marketplace\Marketplace;
use kennethormandy\marketplace\events\PayeesEvent;

class PayeesService extends Component
{
    // public const EVENT_BEFORE_DETERMINE_PAYEES = 'EVENT_BEFORE_DETERMINE_PAYEES';
    public const EVENT_AFTER_DETERMINE_PAYEES = 'EVENT_AFTER_DETERMINE_PAYEES';
  
    public function init()
    {
        parent::init();
    }
    
    public function getGatewayId(Order $order) {
      // TODO Pro, more than one line item allowed, probably all with
      //      the same payee at first. Similar TODO in Marketplace.php
      // Only supports one line item right now,
      // otherwise we’d probably need different
      // Stripe transfer approach
      $lineItemOnly = $order->lineItems[0];
      $purchasable = $lineItemOnly->purchasable;

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
      
      if ($this->hasEventHandlers(self::EVENT_AFTER_DETERMINE_PAYEES)) {
          $event = new PayeesEvent([ 'order' => $order ]);
          $result = $this->trigger(self::EVENT_AFTER_DETERMINE_PAYEES, $event);
          
          // TODO Check type is User
          // TODO Allow return of User, or string which we treat as the $stripeConnectHandle?
          if (isset($result)) {
            $purchasablePayeeUser = $result;
          }
      }

      $stripeConnectHandle = Marketplace::$plugin->handlesService->getButtonHandle($purchasablePayeeUser);

      // TODO Deal with a missing handle better, since you
      //      could have used the Event to give us a User that
      //      doesn’t actually have the field

      $payeeStripeAccountId = $purchasablePayeeUser[$stripeConnectHandle];

      // There is another conditional in Marketplace.php rather than
      // here that notifies if $payeeStripeAccountId is missing—
      // should that live in the service instead?

      return $payeeStripeAccountId;
    }
}
