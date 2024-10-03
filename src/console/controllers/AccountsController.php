<?php

namespace kennethormandy\marketplace\console\controllers;

use Craft;
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

        if (!$account) {
            ConsoleHelper::output('No account found via an element ID of “' . $this->elementId . '”');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $oldAccountId = $account->getAccountId();

        // $oldIsConnected = $account->getIsConnected();

        // TODO Could use Stripe API to validate the new account ID with a
        // `--validate` flag too, ie. throw if the new account ID isn’t actually
        // an account ID on Stripe. Similar to `getIsConnected()`.

        ConsoleHelper::output('Replacing account ID “' . $oldAccountId . '” with new account ID “' . $this->accountId . '”…');

        $accountIdHandle = Marketplace::$plugin->handles->getButtonHandle();
        $account->setFieldValue($accountIdHandle, $this->accountId);
        $saved = Craft::$app->elements->saveElement($account);

        if (!$saved) {
            ConsoleHelper::output('Unable to save new field value');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        ConsoleHelper::output('Done.');
        return ExitCode::OK;
    }
}
