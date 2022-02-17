---
title: "Dynamically set the Payee"
---

How to dynamically support multiple Payees on a single set of products, or modify the Payee based on some other condition specific to your site.

After installing the plugin, add a new module to your Craft site, ex. `modules/CustomPayeeModule.php`:

```php {32,77}
<?php
namespace modules;

use Craft;
use yii\base\Module;
use yii\base\Event;
use craft\elements\User;
use kennethormandy\marketplace\services\PayeesService;

class CustomPayeeModule extends Module
{
    public function init()
    {
        // The usual
        
        // Set a @modules alias pointed to the modules/ directory
        Craft::setAlias('@modules', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\controllers';
        }

        parent::init();
        
        // Custom part starts here

        Event::on(
            PayeesService::class,
            PayeesService::EVENT_AFTER_DETERMINE_PAYEE,
            function (Event $event) {
                Craft::info("Handle EVENT_AFTER_DETERMINE_PAYEE event here", __METHOD__);

                // The $event gives you access to:
                // The Line Item: `$event->lineItem`
                // The User’s Stripe Account ID: `$event->gatewayAccountId`
                // Currently, the `$event->gatewayAccountId` will be set to the
                // ID for the User set on the Product, or will be null if there
                // is none. We want to change it to the User that was submitted
                // from the dropdown.

                $lineItem = $event->lineItem;

                // Using Craft Commerce Lite, so there’s only one
                // Line Item anyway
                $snapshot = $lineItem->snapshot;

                // The Options on the Line Item, which includes
                // the ID of the User we want to pay from the dropdown.
                $options = $snapshot['options'];
                $userToPayId = $options['myUserToPayId'];

                Craft::info("[CustomPayeeModule] " . $userToPayId, __METHOD__);

                if (!isset($userToPayId)) {
                  return;
                }

                // Find the User
                $userToPay = User::find()
                    ->id($userToPayId)
                    ->one();

                Craft::info("[CustomPayeeModule] [User] " . $userToPay, __METHOD__);

                // The name you gave your Connect button field, so we can pull
                // it from the User you actually want to pay. I used
                // “platformConnectButton” like in the README
                $connectButtonFieldName = 'platformConnectButton';
                $userToPayAccountId = $userToPay[$connectButtonFieldName];
                
                // Modify the User that was selected via the checkout options (or
                // however else you want to set up the UI), rather than using the
                // default Payee that would typically be set on the Commerce Product.
                $event->gatewayAccountId = $userToPayAccountId;
            }
        );
        
    }
}
```

In `app.php`, load your new module, as per usual:

```php {4,7}
return [
    'modules' => [
        'my-module' => \modules\Module::class,
        'custom-payee-module' => \modules\CustomPayeeModule::class,
    ],
    'bootstrap' => [
      'custom-payee-module',
    ],
];
```

That is the extent of the custom code, the rest is based on however you want to template it.

Now, what you’ll want to do is modify the actual Twig templates, so you can add the `myUserToPayId` option to the Line Item. For example, on a Product’s Add to Cart form:

```twig
{# The name you gave your Marketplace Connect Button field #}
{% set connectButtonFieldName = 'platformConnectButton' %}

<select name="options[myUserToPayId]">
  {% for user in craft.users.all() %}
    {% if user[connectButtonFieldName] is defined and user[connectButtonFieldName] %}
      <option value="{{ user.id }}">{{ user }}</option>
    {% endif %}
  {% endfor %}
<select>
```

Using the example templates, that would give you something like:

<img width="654" alt="Screen Shot 2020-12-27 at 12 52 37 PM" src="https://user-images.githubusercontent.com/1581276/103179584-7735fb80-4842-11eb-800e-f953d4ba6c61.png" />

Of course, you could also make it a hidden input and change the option based on something like the route instead, depending on what you have in mind for your platform.