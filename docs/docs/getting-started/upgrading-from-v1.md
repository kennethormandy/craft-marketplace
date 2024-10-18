---
title: Upgrading from Marketplace v1
---

This guide will help you upgrade from Marketplace v1 for Craft CMS v3 to Marketplace v4 (not yet tagged) for Craft CMS v4.

The changelog has the full list of changes, but it’s unlikely you’ll need to change anything outside of this guide.

## OAuth Client

The OAuth Client plugin is no longer required as a dependency. You can remove it from your `composer.json`, at the same time as you add the new version number for Craft and Marketplace.

## Environment Variables

Marketplace v1 offered opt-in access to some additional features through an environment variable.

After the upgrade, you can remove this environment variable from all environments and example `.env` files, as it does not do anything anymore:

```
MARKETPLACE_PRO_BETA="true"
```

All the features this variable enabled have been brought into the this new version of Marketplace.

## Services

All services now have conventional paths, ex. `Payee` not `PayeeService`. Replace:

```php
use kennethormandy\marketplace\services\FeesService;
use kennethormandy\marketplace\services\PayeesService;
```

…with:

```php
use kennethormandy\marketplace\services\Fees as FeesService;
use kennethormandy\marketplace\services\Payees as PayeesService;
```

Of course, you may also omit the `as` alias, and update the name later in the code to just `Fees` and `Payees`.

It’s *very* unlikely you’re importing the Service classes anywhere else besides as described above, but if you are, they have all been renamed to remove the word `Service`:

- `kennethormandy\marketplace\services\AccountsService` → `kennethormandy\marketplace\services\Accounts`
- `kennethormandy\marketplace\services\FeesService` → `kennethormandy\marketplace\services\Fees`
- `kennethormandy\marketplace\services\HandlesService` → `kennethormandy\marketplace\services\Handles`
- `kennethormandy\marketplace\services\PayeesService` → `kennethormandy\marketplace\services\Payees`

## Account ID

If you used the `PayeeEvent` to dynamically determine the Payee, `$gatewayAccountId` is now just `$accountId`.

Similarly, in the `Payees` service, the `getGatewayAccountId()` function is now `getAccountId()`.

## Fees

Before upgrading, note what your global fee is currently set to (if anything).

Marketplace Lite v1 supported a single flat-rate or percentage fee. Marketplace v4 supports a single fee multiple, so any percentage fee can be ported to this new global setting. For example, if your fee was previously 10%, you would fill in `0.10` as your new fee.

A beta feature of **Marketplace Pro** v1 allowed more comlpex layering of fees, but in practice it is much easier and more clear to do this in code. Fees are no longer stored in the database. Instead, you can entirely customize the platform fee per line item, which makes it possible to charge a different platform fee for different products, different payees, etc.

If you were using **Marketplace Lite** v1 with a fee in the database, you can migrate it by checking what the fee was set to. It would either be a single flat fee or a percentage fee.

For example, for a 5% percentage fee on all line items:

```php
use Craft;
use yii\base\Event;
use kennethormandy\marketplace\events\FeesEvent;
use kennethormandy\marketplace\services\Fees;

// …

Event::on(
    Fees::class,
    Fees::EVENT_AFTER_CALCULATE_FEES_AMOUNT,
    function (FeesEvent $event) {
        $lineItem = $event->sender;
        $order = $event->order;
        $product = $lineItem->purchasable->product;
        $lineItemTotal = $lineItem->total;

        $event->amount = $lineItemTotal * 0.05;
    }
);
```

…or to set a $5 flat fee on all line items (without taking multi-currency into account):

```php
use Craft;
use yii\base\Event;
use kennethormandy\marketplace\events\FeesEvent;
use kennethormandy\marketplace\services\Fees;

// …

Event::on(
    Fees::class,
    Fees::EVENT_AFTER_CALCULATE_FEES_AMOUNT,
    function (FeesEvent $event) {
        $lineItem = $event->sender;
        $order = $event->order;
        $product = $lineItem->purchasable->product;

        $event->amount = 50.00;
    }
);
```

If you need help migrating your old fee, feel free to [send me an email](mailto:hello+marketplace@kennethormandy.com).

## Settings

It’s worth reviewing the plugin’s settings when you upgrade.

### Stripe Secret Key

You should not need to make any changes to the Stripe Secret Key—this should be ported from Marketplace v1.

It should be set to an environment variable, and will almost certainly be the same environment variable you have filled in for the Stripe for Craft Commerce gateway.

### Default Fee Multiplier

The default fee multiplier allows you to set a simple platform-wise percentage fee, ex. `0.05` for a 5% fee. In most cases, you’ll want to customize this using events, as described below under [Fees](#fees).

### Use Payee Field Type

Marketplace v1 had a user relationship field type to manually set a user-based payee on a product. To my knowledge, no one was using this field type; it is now deprecated and opt-in, and will be removed in the next major version of Marketplace.

If you still require this field type, you can enable it again by creating a `config/marketplace.php` config file in your project, with the `usePayeeFieldType` setting:

```php title=config/marketplace.php
<?php

return [
    // Enable the deprecated Payee field type
    'usePayeeFieldType' => true,
];
```

If you were, in fact, using this field type and need help migrating your field’s content, feel free to [send me an email](mailto:hello+marketplace@kennethormandy.com) for a more comprehensive solution.

The preferred approach (also supported in Marketplace v1) is described in both the [full tutorial](../tutorial/introduction.md) and the [Payees Event](../advanced/events.md#payees-events) docs.

### OAuth

Onboarding Stripe Connect Express accounts using OAuth is not supported for new Stripe accounts, and deprecated for existing Stripe accounts.

It can still be configured using these settings if you so choose (and the platform Stripe account supports it).

You would have previously configured Stripe to have the OAuth Plugin as a URL within the Stripe OAuth settings. This setting can be found in the Stripe dashboard under: Settings → Connect → Onboarding options → OAuth.

If you’d like to keep connecting accounts via OAuth, you can replace this the old URL with the new, Marketplace-specific one provided on the plugin settings screen.

Most platforms will either want or need to ignore this setting.

## Template Helpers

There is a new Twig template helper, to generate the most common form patterns for you. You may which to remove your default account connection form in favour of the following:

```twig
{{ craft.marketplace.renderConnector() }}
```

…when the Marketplace Connect Button field is stored on the user field layout.

If the Marketplace Connect Button field is stored on a different element, ex. and entry, which represents an organization:

```twig
{% set exampleOrg = craft.entries().section('organizations').one() %}

{{ craft.marketplace.renderConnector(exampleOrg) }}
```
