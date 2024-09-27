<?php

namespace kennethormandy\marketplace\behaviors;

use kennethormandy\marketplace\Marketplace;
use yii\base\Behavior;

/**
 * @public craft\base\Element $owner
 */
class MarketplaceAccount extends Behavior
{
    public function getAccountId(): ?string
    {
        $accountId = null;
        $accountIdHandle = Marketplace::$plugin->handles->getButtonHandle();
        $account = Marketplace::$plugin->accounts->getAccount($this->owner, false);

        if ($account) {
            $accountId = $account->getFieldValue($accountIdHandle);
        }

        return $accountId;
    }

    public function getIsConnected(): bool
    {
        // Get the account without falling back to the current user,
        // because the owner is already the element we need.
        $account = Marketplace::$plugin->accounts->getAccount($this->owner, false);
        $isConnected = Marketplace::$plugin->accounts->isConnected($account);

        return $isConnected;
    }
}
