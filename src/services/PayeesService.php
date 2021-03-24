<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\commerce\models\LineItem;
use craft\elements\User;
use kennethormandy\marketplace\events\PayeeEvent;
use kennethormandy\marketplace\Marketplace;
use putyourlightson\logtofile\LogToFile;

class PayeesService extends Component
{
    public const EVENT_BEFORE_DETERMINE_PAYEE = 'EVENT_BEFORE_DETERMINE_PAYEE';
    public const EVENT_AFTER_DETERMINE_PAYEE = 'EVENT_AFTER_DETERMINE_PAYEE';

    public function init()
    {
        parent::init();
    }

    public function getGatewayAccountId(LineItem $lineItem)
    {
        $purchasable = $lineItem->purchasable;
        $event = new PayeeEvent();
        $event->lineItem = $lineItem;
        $event->gatewayAccountId = null;
        $purchasablePayeeUser = null;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_DETERMINE_PAYEE)) {
            $this->trigger(self::EVENT_BEFORE_DETERMINE_PAYEE, $event);
        }

        $payeeHandle = Marketplace::$plugin->handlesService->getPayeeHandle();

        if (
            isset($purchasable[$payeeHandle]) &&
            $purchasable[$payeeHandle] !== null &&
            $purchasable[$payeeHandle]
        ) {
            if (is_numeric($purchasable[$payeeHandle])) {
                // Craft Commerce v3 Digital Products
                $payeeUserId = $purchasable[$payeeHandle];
                $purchasablePayeeUser = User::find()->id($payeeUserId)->one();
            } else {
                // Craft Commerce v2 Digital Products?
                $purchasablePayeeUser = $purchasable[$payeeHandle]->one();
            }
        } elseif (
            isset($purchasable->product[$payeeHandle]) &&
            $purchasable->product[$payeeHandle] !== null &&
            $purchasable->product[$payeeHandle]
        ) {
            // Typcial products
            $payeeUserId = $purchasable->product[$payeeHandle];
            $purchasablePayeeUser = User::find()->id($payeeUserId)->one();
        } else {
            LogToFile::info(
                '[Marketplace] [PayeesService] No User Payee Account ID set in Craft CMS.',
                'marketplace'
            );

            // We don’t return here, because people can still use
            // EVENT_AFTER_DETERMINE_PAYEE to set the a User Payee Account ID
        }

        $payeeStripeAccountId = null;

        if (isset($purchasablePayeeUser)) {
            $stripeConnectHandle = Marketplace::$plugin->handlesService->getButtonHandle($purchasablePayeeUser);
            $payeeStripeAccountId = $purchasablePayeeUser[$stripeConnectHandle];    
        }

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
