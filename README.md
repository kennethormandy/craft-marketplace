# Marketplace

## Getting started

```sh
# Require OAuth plugins (these have been modified and I need to get the changes merged)
composer require venveo/craft-oauthclient
composer require adam-paterson/oauth2-stripe

# install OAuth plugins, and the Marketplace plugin
./craft install/plugin oauthclient
./craft install/plugin marketplace
```

## Features

- Includes two new fields: Marketplace Button and Marketplace Payee
- By adding the Marketplace Button to User profiles, users can register for Stripe Connect Express (or, in theory, regular), via the Craft OAuth plugin, and their account ID will be stored
- By adding the Marketplace Payee field to products, you can say a certain product should be paid to a user
- For single line-item orders, when paying, the order will be paid to the payee
- There is a \$0 transaction fee applied, but that is something we will expose via settings
- The charge is not made `on_behalf_of` users, but we could decide to expose that via a setting
- After authorizing, users can click a button in their profile and see their payment schedule
- Users can see all orders where the first/only line item has them as the payee
- When refunding an order, the associated transfer is also reversed from the connected account

### Add Stripe OAuth app

~~**Important** Manually made this change in vendor package, and haven’t committed it yet. Need to make this change to base url: https://github.com/terehru/oauth2-stripe/commit/94d6f5652e4bd468d12511eb0d6fe82cfea99dad~~ Customizing this without hard-coding the URL has been opened as a PR, I am using my own version here in the meantime.

Correct way would probably be to add support for an option that would change the base url, but looks like I would need to add this to craft-oauthclient (doesn’t have any mechanism of configuring these URLs) and to oauth2-stripe (might let you set a base url instead, I got this working) so for now, adding the express string to the URL is easier

1. Register new app
2. Name “Stripe” or “Stripe Connect” and handle must be “stripe”
3. Set Client ID (the specific Stripe Connect Client ID from the Stripe Connect settings) and Client Secret (your normal Stripe Secret Key) to Stripe environment variables
4. Set provider to Stripe
5. Set scope to `read_only` or `read_write`, not sure which yet (`read_only` seems fine with what I’ve tested so far, but might need `read_write` to not just read existing transactions)
6. Add the Redirect URI to Stripe, so Stripe can redirect users back to your application after connecting with the OAuth flow

### Add Stripe button field to user profiles

- Add a new field that uses the Marketplace Button field type
- Add this to user profiles as appropriate for your platform
- When you connect accounts, right now the associated token is always based on the logged in user account, rather than the profile of the person you are looking at. ie. you have to “Login as user” to test connecting their account, and you shouldn’t be able to see this field (or it should be disabled) unless you are looking at your own profile

~~In the future, this plugin might automatically install `venveo/craft-oauthclient` and `adam-paterson/oauth2-stripe` for you, but for now it remains a separate plugin you install yourself, and then Marketplace uses.~~ These have been customized for the moment. Also, it is possible to create a OAuth App programatically with `venveo/craft-oauthclient`, so we could do that too. But we’d still need the user’s Stripe environment variables anyway.

And this also means if you wanted to do any additional customization, the OAuth flow is already handled for you. You still get all the benefits of the parent plugin.

## License

See the [LICENSE.md](./LICENSE.md) file.
