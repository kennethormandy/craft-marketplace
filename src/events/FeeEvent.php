<?php
/**
 * @since 1.6.0
 */

namespace kennethormandy\marketplace\events;

use craft\commerce\models\LineItem;
use craft\events\ModelEvent;
use kennethormandy\marketplace\models\Fee;

class FeeEvent extends ModelEvent
{
    /**
     * @var LineItem
     */
    public $lineItem;

    /**
     * @var Fee
     */
    public $fee;
}
