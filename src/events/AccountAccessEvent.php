<?php

namespace kennethormandy\marketplace\events;

use craft\base\Element;
use craft\events\CancelableEvent;

/**
 * @since 2.0.0
 */
class AccountAccessEvent extends CancelableEvent
{
    /**
     * The element, typically a user, that is connected to the gateway (ie. Stripe)
     * via a [MarketplaceConnectButton](../fields/MarketplaceConnectButton) field
     */
    public $sender;

    /**
     * The account ID from the gateway (ie. Stripe)
     */
    public ?string $accountId = null;
}
