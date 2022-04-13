# Install Marketplace Pro Beta

This guide will help you install Marketplace Pro Beta.

Marketplace Pro has not been published to the Craft Plugin Store yet, but it has been running in production for over 6 months, and will likely be published as stable in the near future.

This process will be familiar if you have ever instaled a Composer package from a GitHub repository, or loaded a custom plugin into your Craft project, [per the Craft docs](https://craftcms.com/docs/3.x/extend/plugin-guide.html#loading-your-plugin-into-a-craft-project).

## Who should use Marketplace Pro

Marketplace Lite pairs with Craft Commerce Lite’s single line item orders. If you are using Craft Commerce Pro, and your user interface limits customers to single line item orders (ex. a “Buy now” button with no cart), it will work equally well for you. Marketplace Lite also includes support for Craft Commerce Pro and orders with multiple line items, when all line items have the same payee.

Marketplace Pro is intended to pair with Craft Commerce Pro, when your marketplace needs to allow customers to purchase from two or more different payees in a single order. The pro version also supports custom fee logic via an event, ex. rather than defining one fee through the control panel, you can dynamically calculate your platform’s fee based on other details, like the payee, product, whether any discounts are in place, etc.

## Update your `composer.json`

Make the following changes to your `composer.json` file, to allow you to install Marketplace Pro Beta.

- Set the [minimum-stability](https://getcomposer.org/doc/04-schema.md#minimum-stability) to `"dev"`.
- Set [prefer-stable](https://getcomposer.org/doc/04-schema.md#prefer-stable) to `false`, because you’d prefer this version of Marketplace to the current stable release. (?)
- Add the repository config.

```json
{
  "minimum-stability": "dev",
  "prefer-stable": false,
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/kennethormandy/craft-marketplace"
    }
  ]
}
```

Then, in your terminal, require the current Marketplace Pro Beta:

```sh
composer require kennethormandy/craft-marketplace:dev-main#f2d5f4eafa4793cf831cc45a65661ae415d9096e
```

## Add an environment variable

Add the following environment variable in all your environments, to enable the Marketplace Pro Beta features:

```sh
MARKETPLACE_PRO_BETA=true
```

## Continue with the installation process

If you already had Marketplace installed, then you are done: you are now running Marketplace Pro Beta.

If you are installing Marketplace for the first time in your project, you can now install the plugins through the Craft CMS dashboard, or using the command line:

```sh
# Install OAuth Client plugin
./craft install/plugin oauthclient

# Install Marketplace plugin
./craft install/plugin marketplace
```

From there, you’ll want to complete the [Configure sections of the getting started guide](https://github.com/kennethormandy/craft-marketplace#install), get Stripe Connect set up.
