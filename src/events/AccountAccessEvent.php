<?php

namespace kennethormandy\marketplace\events;

use craft\base\Element;
use craft\events\CancelableEvent;

/**
 * @since 2.0.0
 */
class AccountAccessEvent extends CancelableEvent
{
    /** @var Element - The element, typically a user, that is connected to the gateway (ie. Stripe) via a MarketplaceConnectButton field   */
    public $sender;

    /**
     * @var null|string - The account ID from the gateway (ie. Stripe)
     */
    public ?string $accountId = null;
}
