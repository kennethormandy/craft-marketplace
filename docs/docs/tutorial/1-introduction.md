---
title: Introduction
---

import RequirementsPartial from '../_shared/requirements.md'

# Build a marketplace using Craft&nbsp;CMS

This guide will walk you through creating an example, coffee-themed marketplace that sells coffees from multiple roaster businesses. 

It’s built using:

- [Craft CMS](https://craftcms.com/docs/4.x/)
- [Craft Commerce](https://craftcms.com/docs/commerce/4.x/)
- [Stripe for Craft Commerce](https://github.com/craftcms/commerce-stripe/tree/4.x/#readme), and
- Marketplace

…using a fresh install of Craft.

End customers will be able to buy coffee from multiple rosters at once.

Roasters will receive their portions of split payments automatically, and your platform will keep a specified fee. They will also be able to create and edit products, get paid, and view and manage their financial details, without manual work from you.

At the end of the tutorial, you’ll have a better understanding of how you can use and customize Marketplace and Stripe for your own comprehensive marketplace.

## Requirements

This guide assumes you and are also running [DDEV](https://ddev.readthedocs.io/en/stable/) for local development, just like in [Craft’s quick start](https://craftcms.com/docs/4.x/install.html#quick-start) and more comprehensive [getting started tutorial](https://craftcms.com/docs/getting-started-tutorial/).

If you are *not* using DDEV, all the console commands in this tutorial are still relevant, but don’t need to be prefixed with `ddev`.

With your fresh install of Craft ready, install the aforementioned plugins. You can do this via the Plugin Store within the Craft control panel, or using the command line.

<RequirementsPartial />
