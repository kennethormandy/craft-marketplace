<?php

namespace kennethormandy\marketplace\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console as ConsoleHelper;
use craft\commerce\elements\Order;
use kennethormandy\marketplace\Marketplace;
use yii\console\ExitCode;

/**
 * Transfers controller
 */
class TransfersController extends Controller
{
    public $defaultAction = 'index';

    /**
     * @var int $orderId
     */
    public $orderId;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        switch ($actionID) {
            case 'index':
                // $options[] = '...';
                break;
            case 'create':
                $options[] = 'orderId';
                break;    
        }
        return $options;
    }

    /**
     * marketplace/transfers command
     */
    public function actionIndex(): int
    {
        // ...
        return ExitCode::OK;
    }

    /**
     * Create transfers for an existing order.
     * 
     * Example:
     * 
     * ```sh
     * php craft marketplace/transfers/create --order-id 999
     * ```
     * 
     * If, for some reason, you need to run payment splitting on an existing order—ex. if you had an error unrelated to Marketplace
     * that prevented Marketplace from running after an order was completed—you can try and run it again using this console command.
     * 
     * This runs the same codepath as during checkout, with the following safeguards:
     * 
     * - Marketplace will not create more transfers if there is already a transfer in place on Stripe
     * - Stripe will never let you create transfers that total more than the original payment total (ex. if the order total is $20, Stripe would not let you make three $10 transfers)
     * 
     */
    public function actionCreate(): int
    {
        $orderId = $this->orderId;

        if (!$this->orderId) {
            ConsoleHelper::output('An `--order-id` is required.');
            return ExitCode::USAGE;
        }

        $order = Order::find()->id($orderId)->one();

        if (!$order) {
            ConsoleHelper::output('Could not find order “' . $orderId . '”');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        try {
            ConsoleHelper::output('Creating transfers for order “' . $orderId . '”…');
            Marketplace::$plugin->transfers->createTransfersForOrder($order, true);
        } catch (\Exception $e) {
            ConsoleHelper::output($e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }

        ConsoleHelper::output('Done.');

        return ExitCode::OK;
    }
}
