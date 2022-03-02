---
title: "Accounts"
---

## Create Login Link

Once a Payee has their account connected, you can display a button that will give them access to a simplified Stripe dashboard.

```html
{# Whatever you called your Marketplace Connect Button handle,
 # ex. `currentUser.platformConnectButton` #}
{% set fieldHandle = 'platformConnectButton' %}
{% set connectedAccountId = currentUser[fieldHandle] %}

<form target="_blank" method="POST" accept-charset="UTF-8">
  {{ csrfInput() }}
  {{ actionInput('marketplace/accounts/create-login-link') }}

  {# This hidden input sends the accountId along to the action #}
  {{ input('hidden', 'accountId', connectedAccountId, { id: 'accountId' } )}}

  <button type="submit" data-test="connect">Open Dashboard</button>

  {% if errorMessage is defined %}
    <p>{{ errorMessage }}</p>
  {% endif %}
</form>
```

This makes it possible for your Payees to see their payout timing, and a few other Stripe-specific details.

### Redirect

The Stripe Connect Express Dashboard allows for a redirect link. This is used to point back to your platform, and will also be used as a redirect if the user explicitly logs out.

By default, the referring page (ie. the page with your `create-login-link` form) will be used as the redirect location.

If you’d like to customise this link, you can add a [Craft CMS `redirectInput`](https://craftcms.com/docs/3.x/dev/functions.html#redirectinput) to the form instead: 

```html
{{ redirectInput('/see-you-later') }}
```

### Error Message

If there’s an error when creating the login link, a Flash message will be shown, if these are set up in your templates. An error message can also be provided to the form by using:

```html
{% if errorMessage is defined %}
  <p>{{ errorMessage }}</p>
{% endif %}
```

An error could occur if:

- The account ID doesn’t actually exist on your Stripe account—ex. you are using [Live Stripe keys](https://stripe.com/docs/keys#test-live-modes), but now you are trying to access an account connected in Test mode
- There’s an issue reaching the Stripe API
- A user is trying to access an account you’ve revoked from Stripe, but still exists in Craft
- A user is trying to access an account that doesn’t match their own account ID

In any of thse cases, a more detailed error message is logged to the `marketplace.log` file.

<!-- This is the same convention used by [Craft CMS’ front-end login form](https://craftcms.com/knowledge-base/front-end-user-accounts#login-form). -->
