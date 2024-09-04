<?php

namespace kennethormandy\marketplace\services;

use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use kennethormandy\marketplace\events\FeesEvent;

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
        
        // This will get consolidated with calculateFeesAmount, once global
        // fees are changed to all work at the LineItem level, instead
        // of a the Order level.

        if ($this->hasEventHandlers(self::EVENT_AFTER_CALCULATE_FEES_AMOUNT)) {
            $this->trigger(self::EVENT_AFTER_CALCULATE_FEES_AMOUNT, $event);
        }

        return $event->amount;
    }
}
