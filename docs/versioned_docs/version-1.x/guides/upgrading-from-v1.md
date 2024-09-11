---
title: Upgrading from Marketplace v1
---

## OAuth Client

The OAuth Client plugin is no longer required as a dependency. You can remove it while upgrading Craft and Marketplace.

## Fees

Before upgrading, note what your global fee is currently set to.

Marketplace Lite v1 supported a single flat-rate or percentage fee. Marketplace v2 supports a single fee multiple, so any percentage fee can be ported to this new global setting. For example, if your fee was previously 10%, you would fill in `0.10` as your new fee.

Fees are no longer stored in the database. A beta feature of Marketplace Pro allowed more comlpex layering of fees, but in practice it is much easier and more clear to do this in code.

Instead, you can entirely customize the platform fee per line item, which makes it possible to charge a different platform fee for different products, different payees, etc.

## Account ID

If you used the `PayeeEvent` to dynamically determine the Payee, `$gatewayAccountId` is now just `$accountId`.

Similarly, in the `Payees` service, the `getGatewayAccountId()` function is now `getAccountId()`.

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

## Changelog

The changelog details the full changes, but the majority of these are very unlikely to be used directly by your application.
