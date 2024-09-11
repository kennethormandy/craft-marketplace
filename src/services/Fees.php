<?php

namespace kennethormandy\marketplace\services;

use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use kennethormandy\marketplace\events\FeesEvent;
use kennethormandy\marketplace\Marketplace;

class Fees extends Component
{
    public const EVENT_BEFORE_CALCULATE_FEES_AMOUNT = 'beforeCalculateFeesAmount';
    public const EVENT_AFTER_CALCULATE_FEES_AMOUNT = 'afterCalculateFeesAmount';

    public function init(): void
    {
        parent::init();
    }

    /**
     * @param LineItem $lineItem
     * @param Order $order
     * @return int
     */
    public function calculateFeesAmount(LineItem $lineItem, Order $order)
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

        $defaultFeeMultiplier = Marketplace::$plugin->settings->getdefaultFeeMultiplier();

        if ($defaultFeeMultiplier && $event) {
            $event->amount = $event->sender->total * $defaultFeeMultiplier;

            Marketplace::$plugin->log('Calculated fee amount: ' . $event->amount);
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_CALCULATE_FEES_AMOUNT)) {
            $this->trigger(self::EVENT_AFTER_CALCULATE_FEES_AMOUNT, $event);
        }

        Marketplace::$plugin->log('Final calculated fee amount: ' . $event->amount);

        return $event->amount;
    }
}
