---
title: Events
---

Marketplace includes a number of events that you can use to customize its behaviour for your project:

- [Account Events](#accounts-events)
- [Fees Events](#fees-events)
- [Payee Events](#fees-events)

To make use of these events, you’ll need a custom module in your project where you can listen for the events.

You can do this by following Craft’s guide on [using events in a custom module](https://craftcms.com/knowledge-base/custom-module-events), or you can use the official [Generator](https://craftcms.com/docs/4.x/extend/generator.html) package to scaffold the module for you, ex:

```sh
composer require craftcms/generator --dev
php craft make module
```

## Accounts Events

- `kennethormandy\marketplace\services\Accounts`
  - `Accounts::EVENT_BEFORE_ACCOUNT_ACCESS`
  - `Accounts::EVENT_AFTER_ACCOUNT_ACCESS`

### Before Account Access

### After Account Access

## Fees Events

- `kennethormandy\marketplace\services\Fees`
  - `Fees::EVENT_BEFORE_CALCULATE_FEES_AMOUNT`
  - `Fees::EVENT_AFTER_CALCULATE_FEES_AMOUNT`

### Before Calculate Fees

### After Calculate Fees

```php
Event::on(
    Fees::class,
    Fees::EVENT_AFTER_CALCULATE_FEES_AMOUNT,
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

## Payees Events

- `kennethormandy\marketplace\services\Payees`
  - `Payees::EVENT_BEFORE_DETERMINE_PAYEE`
  - `Payees::EVENT_AFTER_DETERMINE_PAYEE`

### Before Determine Payee

### After Determine Payee

How to dynamically support multiple Payees on a single set of products, or modify the Payee based on some other condition specific to your site.

Customize your existing site module where you register event listeners, or add a new module to your site, ex. `modules/SiteModule.php`.

In this example, the platform has an Organizations section with Organization entries. Products have an entries field with the handle `organizations`, which relate the product to the relevant org.

```php {26,67} title="modules/SiteModule.php"
<?php

namespace modules;

use Craft;
use craft\elements\User;
use kennethormandy\marketplace\services\Payees;
use yii\base\Event;
use yii\base\Module;

class SiteModule extends Module
{
    public function init()
    {
        // Set a @modules alias pointed to the modules/ directory
        Craft::setAlias('@modules', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\controllers';
        }

        parent::init();

        Event::on(
            Payees::class,
            Payees::EVENT_AFTER_DETERMINE_PAYEE,
            function(Event $event) {
                Craft::info("Handle EVENT_AFTER_DETERMINE_PAYEE event here", __METHOD__);

                // The `$event` gives you access to:
                //
                // - The Line Item: `$event->lineItem`
                // - The payee’s Stripe Account ID: `$event->accountId`
                //
                // In this event, you can customize how payees are determined. For example,
                // you may have an existing user field on products that determines the payee,
                // or you might look up an organisation entry (what we are doing here) which
                // has the Marketplace Connect Button attached instead.

                $lineItem = $event->lineItem;
                $product = $lineItem->purchasable->product;

                // You might also decide to store other information in the snapshot,
                // and then look it up here, instead of querying the product.
                $snapshot = $lineItem->snapshot;

                $organization = $product->organisation->one() ?? null;

                if (!$organization) {
                    return;
                }

                // If this element has the Marketplace Connect Button field, it will
                // be able to use `getAccountId()`, and may already have one in place.
                $accountId = $organization->getAccountId() ?? null;

                if (!$accountId) {
                    return;
                }

                Craft::info("[SiteModule] " . $event->accountId, __METHOD__);

                // Set the organisation’s account ID on the event
                $event->accountId = $accountId;
            }
        );
    }
}
```
