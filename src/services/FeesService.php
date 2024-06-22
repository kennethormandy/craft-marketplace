<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use craft\helpers\App;
use Exception;
use kennethormandy\marketplace\models\Fee as FeeModel;
use kennethormandy\marketplace\records\FeeRecord;
use kennethormandy\marketplace\events\FeesEvent;
use kennethormandy\marketplace\Marketplace;

class FeesService extends Component
{
    public const EVENT_BEFORE_CALCULATE_FEES_AMOUNT = 'EVENT_BEFORE_CALCULATE_FEES_AMOUNT';
    public const EVENT_AFTER_CALCULATE_FEES_AMOUNT = 'EVENT_AFTER_CALCULATE_FEES_AMOUNT';

    public function init()
    {
        parent::init();
    }

    /**
     * @param $fee
     * @param float $baseAmount Typically, the `$order->itemSubtotal` from Craft Commerce, which is stored as a float.
     * @return int
     */
    public function calculateFeeAmount(FeeModel $fee, float $baseAmount = 0): int
    {
        $feeAmount = 0;

        if ($fee && (int) $fee->value > 0) {
            if ($fee->type === 'price-percentage') {
                if (!isset($baseAmount) || $baseAmount == 0) {
                    Marketplace::$plugin->log('Fee type is “price-percentage,” and provided base amount is 0', [], 'warning');
                }

                // Ex. 12.50% fee stored in DB as 1250
                $percent = ($fee->value / 100);

                // $10 subtotal * 12.5 = 125 cent application fee
                $feeAmount = (float) $baseAmount * $percent;
                $feeAmount = (int) round($feeAmount);
            } elseif ($fee->type === 'flat-fee') {
                // Ex. $10 fee stored in DB as 1000 = 1000 cent fee
                $feeAmount = (int) $fee->value;
            }
        }

        // Must be a positive int, in “cents”
        if (0 > $feeAmount || !is_int($feeAmount)) {
            Marketplace::$plugin->log('Invalid fee. Fee set to 0.', 'marketplace', [], 'warning');

            return 0;
        }

        return $feeAmount;
    }

    /**
     * @param $order
     * @return int
     */
    public function calculateFeesAmount(LineItem $lineItem = null, Order $order)
    {
        $event = new FeesEvent();
        $event->order = $order;

        // TODO Actually support passing along the line item
        $event->sender = $lineItem;

        // $event->fees = $this->getAllFees();
        $event->fees = [];


        $event->amount = 0;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_CALCULATE_FEES_AMOUNT)) {
            $this->trigger(self::EVENT_BEFORE_CALCULATE_FEES_AMOUNT, $event);
        }

        $firstLineItem = $this->_getFirstLineItem($event->order);

        if ($event->fees && count($event->fees) >= 1) {

            // Calculate the fee based on the order subtotal, because with price-percentage
            // we want the entire order subtotal, not the price of the first line item.
            $feeCount = 0;

            foreach ($event->fees as $feeId => $fee) {
                if ($feeCount >= 1 && $this->_isPro()) {
                    break;
                }

                if (
                    $fee->type !== 'flat-fee' ||

                    // Apply flat-fee to the first line item only
                    // This is a work around, until we properly support the flat-fee at the
                    // line item level, where it should be based on applying the fee once
                    // per payee in an order.
                    ($fee->type === 'flat-fee' && $lineItem->id === $firstLineItem->id)
                ) {
                    $currentFeeAmount = $this->calculateFeeAmount($fee, $lineItem->total);

                    // TODO Global fees are in Stripe format, but we are changing the event
                    // hook to accept the amount in Craft format.

                    $event->amount += $currentFeeAmount;
                }

                $feeCount++;
            }    
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_CALCULATE_FEES_AMOUNT)) {
            $this->trigger(self::EVENT_AFTER_CALCULATE_FEES_AMOUNT, $event);
        }

        return $event->amount;
    }

    /**
     * @param LineItem $lineItem
     * @param Order $order
     * @return int
     */
    public function _calculateLineItemFeesAmount(LineItem $lineItem, Order $order)
    {
        $event = new FeesEvent();
        $event->order = $order;
        $event->sender = $lineItem;

        // Get all LineItem fees?
        $event->fees = [];

        $event->amount = 0;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_CALCULATE_FEES_AMOUNT)) {
            $this->trigger(self::EVENT_BEFORE_CALCULATE_FEES_AMOUNT, $event);
        }
        
        // This will get consolidated with calculateFeesAmount, once global
        // fees are changed to all work at the LineItem level, instead
        // of a the Order level.

        if ($this->hasEventHandlers(self::EVENT_AFTER_CALCULATE_FEES_AMOUNT)) {
            $this->trigger(self::EVENT_AFTER_CALCULATE_FEES_AMOUNT, $event);
        }

        return $event->amount;
    }

    private function _getFirstLineItem(Order $order) {
        $firstLineItem = null;

        if (
            isset($order) &&
            isset($order->lineItems) &&
            count($order->lineItems) >= 1
        ) {
            $firstLineItem = $order->lineItems[0];
        }

        return $firstLineItem;
    }

    /**
     * Is Pro.
     *
     * Whether or not this the Pro edition of the plugin is being used.
     *
     * @since 1.6.0
     * @return bool
     */
    private function _isPro()
    {
        if (defined('Marketplace::EDITION_PRO') && Marketplace::$plugin->is(Marketplace::EDITION_PRO)) {
            return true;
        }

        if (App::env('MARKETPLACE_PRO_BETA')) {
            return true;
        }

        return false;
    }
}
