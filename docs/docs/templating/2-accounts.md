---
title: "Accounts"
---

import CodeBlock from '@theme/CodeBlock';
import SnippetHostedOnboarding from '!!raw-loader!./../../../src/templates/_site/hosted-onboarding.twig';

## Render Connector

Once a Payee has their account connected, you can display a button that will give them access to a simplified Stripe dashboard:

```twig
{% set exampleOrg = craft.entries().section('organizations').one() %}

{{ craft.marketplace.renderConnector(exampleOrg) }}
```

This makes it possible for your Payees to see their payout timing, and a few other Stripe-specific details.

## Customize connector

You can apply your own classes and attributes to the default connector, to style it like your site. This is especially relevant if you are using a utility class library like Tailwind.

```twig
{% set exampleOrg = craft.entries().section('organizations').one() %}

{{ craft.marketplace.renderConnector(exampleOrg, {
  form: {
    attributes: {
      'data-my-custom-attribute': 'my-value',
    },
  },
  errorMessage: {
    attributes: {
      class: 'text-red-500',
    }
  },
  submitButton: {
    attributes: {
      class: 'bg-blue-500 text-white',
    },
  },
}) }}
```

## Completely custom connector form

If you need to customize the form beyond attributes and CSS classes, you can entirely re-write the form. The `renderConnector` function renders the following snippet, which you can customize from this point:

<CodeBlock language="twig">{SnippetHostedOnboarding}</CodeBlock>

<!--

### Redirect

The Stripe Connect Express Dashboard allows for a redirect link. This is used to point back to your platform, and will also be used as a redirect if the user explicitly logs out.

By default, the referring page (ie. the page with your `create-login-link` form) will be used as the redirect location.

If you’d like to customise this link, you can add a [Craft CMS `redirectInput`](https://craftcms.com/docs/3.x/dev/functions.html#redirectinput) to the form instead: 

```twig
{{ redirectInput('/see-you-later') }}
```

-->

## Error Messages

An error could occur on the connector if:

- The account ID doesn’t actually exist on your Stripe account—ex. you are using [Live Stripe keys](https://stripe.com/docs/keys#test-live-modes), but now you are trying to access an account connected in Test mode
- There’s an issue reaching the Stripe API
- A user is trying to access an account you’ve revoked from Stripe, but still exists in Craft
- A user is trying to access an account that doesn’t match their own account ID

In any of thse cases, a more detailed error message is logged to the `marketplace.log` file.

If you are customizing your form, the `errorMessage` variable holds the error message. This is the same convention used by [Craft CMS’ front-end login form](https://craftcms.com/knowledge-base/front-end-user-accounts#login-form). If you are using `renderConnector()`, this is already handled for you.
