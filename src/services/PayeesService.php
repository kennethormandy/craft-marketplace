<?php
namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\elements\User;

use kennethormandy\marketplace\Marketplace;

class PayeesService extends Component
{
    public function init()
    {
        parent::init();
    }
    
    public function getGatewayId($purchasable) {
      // NOTE Make argument order rather than purchasable?

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

      return $payeeStripeAccountId;
    }
}
