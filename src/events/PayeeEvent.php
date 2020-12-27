<?php
/**
 *
 * @since 1.1.0
 */

namespace kennethormandy\marketplace\events;

use craft\commerce\models\LineItem;
use craft\events\ModelEvent;

class PayeeEvent extends ModelEvent
{
  /**
   * @var LineItem
   */
  public $lineItem;

  /**
   * @var string
   */
  public $gatewayAccountId = null;
}
