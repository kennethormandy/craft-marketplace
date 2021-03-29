---
title: "Accounts"
---

## Create Login Link

```twig
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

### Redirect

The Stripe Connect Express Dashboard allows for a redirect link. This is used to point back to your platform, and will also be used as a redirect if the user explicitly logs out.

By default, the referring page (ie. the page with your `create-login-link` form) will be used as the redirect location.

If you’d like to customise this link, you can add a [Craft CMS `redirectInput`](https://craftcms.com/docs/3.x/dev/functions.html#redirectinput) to the form instead: 

```twig
{{ redirectInput('/see-you-later') }}
```

<!--

### Errors

- Can’t create a link
- Doesn’t have permission to open the dashboard for that account, and isn’t an Admins

```
{% if errorMessage is defined %}
  <p>{{ errorMessage }}</p>
{% endif %}
```

-->