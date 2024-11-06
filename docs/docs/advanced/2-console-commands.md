# Console Commands

Connected accounts in Stripe are configured separately for Live and Test mode. This means if you try and use your existing Live mode accounts locally, or vice versa, they simply won’t be present, and Marketplace will not be able to do any payment splitting.

Marketplace includes some console commands that can help manage this situation when switching between environments.

## An example

For example, say you take over a project that is using Marketplace and Stripe in production, and you need to set up your local development environment. You might import a copy of your production database as a starting point.

Most likely, you’ll switch from Stripe’s Live mode to Test mode, by changing out your **Publishable API Key** and **Secret API Key** in your `.env` file, [as described in the Stripe for Craft Commerce docs](https://github.com/craftcms/commerce-stripe/tree/4.x?tab=readme-ov-file#secrets).

Now, you’ll be able to check out using Stripe’s test credit card numbers, just like a typical Craft Commerce project—except none of your payment splitting will be working.

This is because the connected accounts your vendors established in production are only connected to Stripe in Live mode only.

You need to create new accounts for them, while using Stripe in Test mode.

Once you’ve created one or two accounts in test mode using your normal onboarding flow, or found some that already exist via the Stripe Dashboard, you might use the following console commands to replace or remove account IDs from the other accounts as you see fit.

## Replacing account IDs

You might use this command to switch from a production account ID, to one that works in test mode.

Typically, you shouldn’t need to run this in production, because vendors should be the ones connecting accounts themselves.

```sh
php craft marketplace/accounts/replace --element-id 123 --account-id acct_aAbBcCdDEeFfGgHh
```

…where `123` is an account, ie. an element with a Marketplace Connect Button field, and
`acct_aAbBcCdDEeFfGgHh` is the new account ID you want it to contain.

:::warning

Currently, this isn’t validated using Stripe, ie. it is possible to give this command an invalid
account ID that doesn’t exist on your Stripe account.

:::
 
## Removing account IDs

You might use this command to clear out an existing account ID in an environment, so you can run through connecting the account again manually.

```sh
craft marketplace/accounts/replace --element-id 123 --account-id acct_aAbBcCdDEeFfGgHh
```
