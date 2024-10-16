<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\commerce\models\LineItem;
use craft\elements\User;
use kennethormandy\marketplace\events\PayeeEvent;
use kennethormandy\marketplace\Marketplace;

/**
 * The Payee service is distinct from the Accounts service, because the Accounts service
 * does not necessarily have anything to do with ecommerce yet. All Payees are Accounts,
 * but not all accounts are Payees (incomplete accounts, disabled accounts, etc.)
 */
class Payees extends Component
{
    public const EVENT_BEFORE_DETERMINE_PAYEE = 'beforeDeterminePayee';
    public const EVENT_AFTER_DETERMINE_PAYEE = 'afterDeterminePayee';

    public function init(): void
    {
        parent::init();
    }

    /**
     * Determine and return the payee’s account ID, based on a Commerce LineItem.
     *
     * @return null|string The gateway account ID. If this is null, there is effectively no payee, so this line item will be treated like a typical Commerce order and not use Marketplace.
     * @since 1.1.0
     */
    public function getAccountId(LineItem $lineItem): ?string
    {
        $purchasable = $lineItem->purchasable;
        $event = new PayeeEvent();
        $event->lineItem = $lineItem;
        $event->accountId = null;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_DETERMINE_PAYEE)) {
            $this->trigger(self::EVENT_BEFORE_DETERMINE_PAYEE, $event);
        }

        // Marketplace no longer does anything between this “before” and “after”
        // event. It used to look up a payee via a (now deprecated) Marketplace
        // custom payee field on the purchasable.

        $payeeStripeAccountId = null;
        $event->accountId = $payeeStripeAccountId;

        if ($this->hasEventHandlers(self::EVENT_AFTER_DETERMINE_PAYEE)) {
            $this->trigger(self::EVENT_AFTER_DETERMINE_PAYEE, $event);
        }

        // There is another conditional in Marketplace.php rather than
        // here that notifies if $payeeStripeAccountId is missing—
        // should that live in the service instead?

        // We use the event here so an end user can override this
        return $event->accountId;
    }
}
