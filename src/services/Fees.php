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

class Fees extends Component
{
    public const EVENT_BEFORE_CALCULATE_FEES_AMOUNT = 'EVENT_BEFORE_CALCULATE_FEES_AMOUNT';
    public const EVENT_AFTER_CALCULATE_FEES_AMOUNT = 'EVENT_AFTER_CALCULATE_FEES_AMOUNT';

    public function init()
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
