<?php
/**
 *
 * @since 1.1.0
 */

namespace kennethormandy\marketplace\events;

use craft\events\ModelEvent;

class PayeesEvent extends ModelEvent
{
  public $order;
}
