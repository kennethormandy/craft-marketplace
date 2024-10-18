---
title: Tutorial
sidebar_position: 1
---

<!-- This is a test, not the most recent draft -->

# Build a marketplace using Craft&nbsp;CMS

This guide will walk you through creating an example, coffee-themed marketplace that sells coffees from multiple roasters. It’s built using:

- [Craft CMS](https://craftcms.com/docs/4.x/)
- [Craft Commerce](https://craftcms.com/docs/commerce/4.x/)
- [Stripe for Craft Commerce](https://github.com/craftcms/commerce-stripe/tree/4.x/#readme), and
- Marketplace

…using a fresh install of Craft.

End customers will be able to buy coffee from multiple rosters at once.

Roasters will receive their portions of split payments automatically, and your platform will keep a specified fee. They will also be able to create and edit products, get paid, and view and manage their financial details, without manual work from you.

At the end of the tutorial, you’ll have a better understanding of how you can use and customize Marketplace and Stripe for your own comprehensive marketplace.

## Requirements

This guide assumes you and are also running [DDEV](https://ddev.readthedocs.io/en/stable/) for local development, just like in [Craft’s quick start](https://craftcms.com/docs/5.x/install.html#quick-start) and more comprehensive [getting started tutorial](https://craftcms.com/docs/getting-started-tutorial/).

If you are *not* using DDEV, all the console commands in this tutorial are still relevant, but don’t need to be prefixed with `ddev`.

With your fresh install of Craft ready, install the aforementioned plugins. You can do this via the Plugin Store within the Craft control panel, or using the command line.

### Craft Commerce

Install Commerce:

```sh
ddev composer install craftcms/commerce
ddev composer craft plugin/install commerce
```

### Stripe for Craft Commerce

Install Stripe for Craft Commerce:

```sh
ddev composer install craftcms/commerce-stripe
ddev craft plugin/install commerce-stripe
```

### Marketplace

Install Marketplace:

```sh
ddev composer install kennethormandy/craft-marketplace
ddev craft plugin/install marketplace
```

## Setup Craft Commerce

<!--

- Add coffee product type
- Add a coffee product

-->

Craft Commerce does not need much configuration at this stage of the project—shipping, taxes, etc. can all be configured later based on how you want to run your marketplace.

The only thing we really need is to add is a product type, so new products can be added, and the Stripe payment gateway, so customers can checkout.

### Add a product type

Add a new “Coffee” product type under Commerce → System Settings → Product Types.

![](https://picsum.photos/id/13/2500/1667)

For now, you can set the Automatic SKU Format to:

```twig
{product.slug}
```

…or leave it blank. We’ll customize it later in the tutorial.

### Add a new product

Now, it’s possible to add new products under Commerce → Products.

![](https://picsum.photos/id/13/2500/1667)

Let’s add medium roast coffee available for purchase:

I’ve added two variants: a whole bean option, for people who want to grind the coffee themselves, and a pre-ground option.

Add one or two more coffee products—just enough to give us something to work with for now.

### Add a payment gateway

Now, you’re ready to add the Stripe payment gateway, so customers will be able to checkout and pay.

>  To add a Stripe payment gateway, open the Craft control panel, navigate to **Commerce** → **System Settings** → **Gateways**, and click **+ New gateway**.
> 
> Your gateway’s **Name** should make sense to administrators _and_ customers (especially if you’re using the example templates).
> 
> ### Secrets
> 
> From the **Gateway** dropdown, select **Stripe**, then provide the following information:
> 
> - Publishable API Key
> - Secret API Key
> - Webhook Signing Secret (See [Webhooks](https://github.com/craftcms/commerce-stripe?tab=readme-ov-file#webhooks) for details)
> 
> Your **Publishable API Key** and **Secret API Key** can be found in (or generated from) your Stripe dashboard, within the **Developers** &rarr; **API Keys** tab. Read more about [Stripe API keys](https://stripe.com/docs/keys).
> 
> > [!NOTE]
> > To prevent secrets leaking into project config, put them in your `.env` file, then use the special [environment variable syntax](https://craftcms.com/docs/4.x/config/#control-panel-settings) in the gateway settings.
> 
> Stripe provides different keys for testing—use those until you are ready to launch, then replace the testing keys in the live server’s `.env` file.

https://github.com/craftcms/commerce-stripe/tree/4.x?tab=readme-ov-file#setup

<!-- Set the keys to environment variables—it’s best practice, and we’re going to use them again for Marketplace -->

### Use the example templates

Craft Commerce comes with [full-featured example templates](https://craftcms.com/docs/commerce/4.x/example-templates.html). We’re going to use these for the user-facing portion of our Marketplace in this tutorial. Copy the templates into your project with the following console command:

```sh
commerce/example-templates --folder-name shop
```

You should now have the templates in the `templates/shop`.

Now you can visit the front-end of the site, and see a typical Craft Commerce install is ready at `/shop`:

```sh
# Open /shop in your default browser
ddev launch shop
```

You should see something like this in the browser:

![](https://picsum.photos/id/13/2500/1667)

## Setup Marketplace

So far, our Craft Commerce site only supports us selling our own products. We want to support multiple vendors.

Let’s start making use of Marketplace.

### Create the connection field

First, create a new Marketplace Connect Button field.

It will represent the connection between Stripe and Craft. For our coffee marketplace, we’ll label it “Platform Connection.”

![](https://picsum.photos/id/13/2500/1667)

### Create a section for vendors

<!--

Decide on a term: Payee, Connected Accounts, Sellers, Vendors. Or use Roasters for the sake of the tutorial.

-->

Your marketplace will need to onboard vendors.

Typically, these will either be represented by [users](https://craftcms.com/docs/4.x/reference/element-types/users) or by [entries](https://craftcms.com/docs/4.x/reference/element-types/entries).

Like Craft, Marketplace leaves it up to you how to model content for your marketplace, but it’s almost always better to choose entries.

This will allow multiple users login to and be associated with a single vendor—even if you don’t need that feature in the short term.

For our coffee marketplace, create a new section called “Organizations.”

![](https://picsum.photos/id/13/2500/1667)

Update the entry type name from “Default” to “Organization,” and add the new “Platform Connection” field to its field layout.

![](https://picsum.photos/id/13/2500/1667)

As with the coffee products, you can now create a few example entries as content to work with:

![](https://picsum.photos/id/13/2500/1667)

### Create an organization field

We’re also going to need an Entries field, so we can relate these roaster Entries to other things in Craft:

![](https://picsum.photos/id/13/2500/1667)

### Update the Coffee product type and products

Now, we have a few roasters and a few coffees (manually) filled in on our marketplace—but nothing has changed on the front-end for end customers. There is still no way to see a specific coffee is coming from a specific roaster. Let’s change that!

Go back to Commerce → System Settings → Product Types → Coffee → Product Fields to edit the 

<!-- 

- Add the field to the coffee product type, so we know which coffee belongs to which roaster
- Create some more roaster entries
- Edit the coffee products you’ve made so far, and give each one to a different roaster

-->

***

- After setting up multiple vendors, and the basic dashboard, the tutorial could say coming soon, more tutorial:
	- Single or “fixed” products across multiple vendors
	- Multiple products with more things to fill in in the dashboard

For now, further reading is front-end user form
