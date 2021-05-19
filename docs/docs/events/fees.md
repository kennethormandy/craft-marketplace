---
title: FeesService Events
---

## Before Calculate Fees

## After Calculate Fees

```php
Event::on(
    FeesService::class,
    FeesService::EVENT_AFTER_CALCULATE_FEES_AMOUNT,
    function (FeesEvent $event) {
        $order = $event->order;
        $lineItems = $order->lineItems;

        // Example conditional. Check something in the
        // product snapshot, and change the fee accordingly.
        if (
            $lineItems[0] &&
            $lineItems[0]->snapshot['title'] === 'My specific product'
        ) {
            // Overwrite the total calculated fee amount on the event.
            // This amount is set as an integer in “cents,” so in this case
            // the Fee will be US$12.34 on a platform using US dollars.
            $event->amount = 1234;
        }

        // In all other cases, the fee would be calculated using your
        // existing global fee settings.
    }
);
```
