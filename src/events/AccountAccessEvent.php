<?php

namespace kennethormandy\marketplace\events;

use craft\base\Element;
use craft\elements\User;
use craft\events\CancelableEvent;

/**
 * @since 2.0.0
 */
class AccountAccessEvent extends CancelableEvent
{
    /**
     * The element, typically a user or org-like entry, that is connected to the
     * gateway (ie. Stripe) via a
     * [MarketplaceConnectButton](../fields/MarketplaceConnectButton) field
     */
    public $sender;

    /**
     * The [User](https://docs.craftcms.com/api/v4/craft-elements-user.html)
     * attempting to access the gateway (ie. Stripe) account
     */
    public User $user;

    /**
     * The account ID from the gateway (ie. Stripe)
     */
    public ?string $accountId = null;
}
