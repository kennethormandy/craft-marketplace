---
title: Variables
---

After installing Marketplace, you’ll have access to `craft.marketplace` in Twig.

The main Twig function available is to render a connector, for vendors to connect their accounts via Stripe:

```twig
{% set exampleOrg = craft.entries().section('organizations').one() %}

{{ craft.marketplace.renderConnector(exampleOrg) }}
```

See also the [the API docs](../api/classes/kennethormandy/marketplace/variables/MarketplaceVariable.md).
