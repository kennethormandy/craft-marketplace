<?php

namespace kennethormandy\marketplace\behaviors;

use craft\helpers\UrlHelper;
use kennethormandy\marketplace\Marketplace;
use yii\base\Behavior;

/**
 * @public craft\base\Element $owner
 */
class MarketplaceAccount extends Behavior
{
    public bool $isConnected = false;

    public function getAccountId()
    {
        $accountId = null;
        $accountIdHandle = Marketplace::$plugin->handles->getButtonHandle();
        $account = Marketplace::$plugin->accounts->getAccount($this->owner, false);

        if ($account) {
            $accountId = $account->getFieldValue($accountIdHandle);
        }

        return $accountId;
    }

    public function setIsConnected(): void
    {
        // Get the account without falling back to the current user,
        // because the owner is already the element we need.
        $account = Marketplace::$plugin->accounts->getAccount($this->owner, false);
        $isConnected = Marketplace::$plugin->accounts->isConnected($account);
        $this->isConnected = $isConnected;
    }

    public function getIsConnected(): bool
    {
        return $this->isConnected;
    }
}
