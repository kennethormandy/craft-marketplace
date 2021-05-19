<?php
/**
 * @since 1.6.0
 */

namespace kennethormandy\marketplace\events;

use craft\commerce\models\Order;
use craft\events\ModelEvent;
use kennethormandy\marketplace\models\Fee;

class FeesEvent extends ModelEvent
{
    // Decided to make this FeesEvent rather than FeeEvent
    // You’d hook into before, and change the global fees, and then let them
    // be calculated using the normal rules. Or, you’d hook into after, and
    // simply change the event entirely yourself. This would let you
    // set the value via line items, but you could also set it based on any
    // other details you might need to check.

    /** @var Fee[] */
    public $fees;

    /** @var Order */
    public $order;

    public $amount;
}
