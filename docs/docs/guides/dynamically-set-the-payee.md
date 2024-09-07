---
title: "Dynamically set the Payee"
---

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

In `app.php`, load your new module, as per usual:

```php {4,7} title="app.php"
return [
    'modules' => [
        'my-module' => \modules\Module::class,
        'custom-payee-module' => \modules\SiteModule::class,
    ],
    'bootstrap' => [
      'custom-payee-module',
    ],
];
```
