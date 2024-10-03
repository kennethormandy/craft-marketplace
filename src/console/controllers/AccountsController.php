<?php

namespace kennethormandy\marketplace\console\controllers;

use Craft;
use craft\base\Element;
use craft\helpers\Console as ConsoleHelper;
use craft\console\Controller;
use kennethormandy\marketplace\Marketplace;
use yii\console\ExitCode;

/**
 * Manages Marketplace gateway (ie. Stripe) accounts.
 */
class AccountsController extends Controller
{
    public $defaultAction = 'index';

    /**
     * @var null|int $elementId
     */
    public $elementId = null;

    /**
     * @var null|string $accountId
     */
    public $accountId = null;

    public function options($actionId): array
    {
        $options = parent::options($actionId);
        switch ($actionId) {
            case 'index':
                // $options[] = '...';
            case 'replace':
                $options[] = 'elementId';
                $options[] = 'accountId';
                break;
        }
        return $options;
    }

    /**
     * Does nothing.
     */
    public function actionIndex(): int
    {
        return ExitCode::OK;
    }

    /**
     * Manually replace an account ID, ex. from Live to Test.
     * 
     * This is intended to be used in staging and development environments,
     * where you’ll likely need to replace production account IDs (ex. from
     * Stripe Live mode) with test account IDs (ie. from Stripe Test mode).
     */
    public function actionReplace(): int
    {
        if (!$this->elementId || !$this->accountId) {
            ConsoleHelper::output('Both `--element-id` and `--account-id` are required.');
            return ExitCode::USAGE;
        }

        $element = Craft::$app->elements->getElementById($this->elementId);

        if (!$element) {
            ConsoleHelper::output('No element exists with an ID of “' . $this->elementId . '”');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $fallbackToCurrentUser = false;
        $account = Marketplace::$plugin->accounts->getAccount($element, $fallbackToCurrentUser);
        $newAccountId = $this->accountId;

        if (!$account) {
            $accountIdHandle = Marketplace::$plugin->handles->getButtonHandle();

            // TODO Could be replaced with more comprehensive checks when adding behaviours
            $hasMarketplaceButton = $element->getFieldLayout()->getFieldByHandle($accountIdHandle);

            if ($hasMarketplaceButton) {
                // It’s an account-like element with no field value yet
                $account = $element;
            } else {
                ConsoleHelper::output('No account found via an element ID of “' . $this->elementId . '”');
                return ExitCode::UNSPECIFIED_ERROR;    
            }
        }

        $oldAccountId = $account ? $account->getAccountId() : '';
        // $oldIsConnected = $account->getIsConnected();

        if ($oldAccountId === $newAccountId) {
            ConsoleHelper::output('The account ID is already ' . $oldAccountId);
            return ExitCode::OK;
        }

        ConsoleHelper::output('Replacing account ID “' . $oldAccountId . '” with new account ID “' . $newAccountId . '”…');

        // TODO Could use Stripe API to validate the new account ID with a
        // `--validate` flag too, ie. throw if the new account ID isn’t actually
        // an account ID on Stripe. Similar to `getIsConnected()`.

        return $this->_setAccountIdAndDone($account, $newAccountId);
    }

    /**
     * Save the account element with a new ID, with appropriate console output.
     */
    private function _setAccountIdAndDone(Element $account, string $newAccountId): int|Element
    {
        $accountIdHandle = Marketplace::$plugin->handles->getButtonHandle();
        $account->setFieldValue($accountIdHandle, $newAccountId);

        $saved = Craft::$app->elements->saveElement($account);

        if (!$saved) {
            ConsoleHelper::output('Unable to save new field value');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        ConsoleHelper::output('Done.');
        return ExitCode::OK;
    }
}
