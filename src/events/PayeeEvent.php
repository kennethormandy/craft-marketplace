<?php
/**
 * @since 1.1.0
 */

namespace kennethormandy\marketplace\events;

use craft\commerce\models\LineItem;
use craft\events\ModelEvent;

class PayeeEvent extends ModelEvent
{
    /**
     * @var LineItem $lineItem - The line item to be used to determine the account ID.
     */
    public LineItem $lineItem;

    /**
     * @var string|null $accountId - The gateway account ID.
     */
    public ?string $accountId = null;
}
