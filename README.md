# Marketplace

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

## Getting started

```sh
# Install OAuth Client plugin
./craft install/plugin oauthclient

# Install Marketplace plugin
./craft install/plugin marketplace
```

Next, you can configure the OAuth Client plugin.

### Add Stripe OAuth app

1. Register new app. The handle must be `stripe`. The name is up to you—probably “Stripe” or “Stripe Connect.”
2. Choose the provider “Stripe” from the dropdown. It will be available in the dropdown as long as you’ve installed the Marketplace plugin too.
3. Set the Client ID (the specific Stripe Connect Client ID from the Stripe Connect settings) and the Client Secret (your normal Stripe Secret Key). Typically, you’d want to use environment variables for these, so they can easily be switched between Stripe’s Test and Live modes in development versus production
4. Set scope to `read_only` or `read_write` (`read_only` seems sufficient with what I’ve tested so far, but might need `read_write` to not just read existing transactions)

Optional additional steps:

- *Use Stripe Connect Express* If you’re using Stripe Connect Express, under “Advanced,” set the Authorize URL to the Stripe Connect Express endpoint: `https://connect.stripe.com/express/oauth/authorize` (otherwise, you’ll be sending potential connected apps to the Standard version of Stripe Connect).
- *Redirect back to your app* Add the Redirect URI from the “Setup Info” tab to your Stripe Connect settings on Stripe. At the time of writing, this is stored in the Stripe Dashboard under: Settings → Connect settings → Integration. Stripe can redirect users back to your application after connecting with the OAuth flow.

### Add Stripe button field to user profiles

- Add a new field that uses the Marketplace Button field type
- Add this to user profiles as appropriate for your platform
- When you connect accounts, right now the associated token is always based on the logged in user account, rather than the profile of the person you are looking at. ie. you have to “Login as user” to test connecting their account, and you shouldn’t be able to see this field (or it should be disabled) unless you are looking at your own profile

~~In the future, this plugin might automatically install `venveo/craft-oauthclient` and `adam-paterson/oauth2-stripe` for you, but for now it remains a separate plugin you install yourself, and then Marketplace uses.~~ These have been customized for the moment. Also, it is possible to create a OAuth App programatically with `venveo/craft-oauthclient`, so we could do that too. But we’d still need the user’s Stripe environment variables anyway.

And this also means if you wanted to do any additional customization, the OAuth flow is already handled for you. You still get all the benefits of the parent plugin.

## License

See the [LICENSE.md](./LICENSE.md) file.
