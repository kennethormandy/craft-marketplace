---
title: Checkout
---

# Checkout

The marketplace is ready to use, but customers aren’t going to easily be able to tell which roaster sells which coffee. Let’s make a few small changes to the example templates, to make that more obvious.

## Customize templates

In the Cart, you might show the roaster name as part of the line item:

```twig title="templates/shop/cart/index.twig"
{# Query the roaster associated with the product #}
{% set roaster = item.purchasable.product.roaster.one() ?? null %}

{% if roaster %}
  <div class="mb-1 text-xs">{{ roaster.title }}</div>
{% endif %}
```

Giving you something like this:

![](./15-template-cart.png)

Similarly, you might want to show the roaster name in the product grid and product detail page, too:

```twig title="templates/shop/products/_product.twig"
<!-- highlight-next-line -->
{% set roaster = product.roaster.one() %}

<div class="text-sm w-2/3">
  <!-- highlight-next-line -->
  {{ roaster.title|default('') }}
</div>
<div class="relative text-lg text-bold mb-2 flex items-baseline leading-tight">
  <div class="w-2/3">
    <a class=" text-blue-500 hover:text-blue-600" href="{{ product.url }}">
      {{ product.title|title }}
    </a>
  </div>
  <div class="w-1/3 text-right">
    <span>{{ product.defaultPriceAsCurrency }}</span>
  </div>
</div>
<p class="text-sm">
  {{ product.description|default('This is a pretend product description, placeholdering here for you to swap with something better.')|t }}
</p>
```

```twig title="templates/shop/products/_includes/grid.twig"
<!-- highlight-next-line -->
{% set roaster = product.roaster.one() %}

<div class="text-sm w-2/3">
  <!-- highlight-next-line -->
  {{ roaster.title|default('') }}
</div>
<div class="relative text-lg text-bold mb-2 flex items-baseline leading-tight">
  <div class="w-2/3">
    <a class=" text-blue-500 hover:text-blue-600" href="{{ product.url }}">
      {{ product.title|title }}
    </a>
  </div>
  <div class="w-1/3 text-right">
    <span>{{ product.defaultPriceAsCurrency }}</span>
  </div>
</div>
<p class="text-sm">
  {{ product.description|default('This is a pretend product description, placeholdering here for you to swap with something better.')|t }}
</p>
```

I’ve made a few other small changes to distinguish coffees: a description field, to override the placeholder description, and a colour field to give each different roaster a Tailwind colour class to use.

![](./16-template-products.png)

## Checkout

Now, you have at least two different roasters onboarded, each with a different coffee. Customers can tell which roaster we are purchasing different coffees from—but can still add whichever ones they want to our cart.

If we were to checkout with both products in our cart, we’d expect the end customer would pay once for the total, we’d keep a 10% fee, and each roaster would get their remaining portion of the money—all without any manual payout management from us.

In a new private browsing window, so you aren’t already logged into Craft, visit the site, add at least two products to your cart, and checkout.

![](./17-checkout-cart.png)

The example templates allow you to skip filling in the shipping portion of the order, by using the step headings. You can skip ahead to payment.

Choose the Stripe gateway, and complete payment using [the Stripe test card number](https://docs.stripe.com/testing#cards):

```
4242 4242 4242 4242
```

You can fill in any valid date and <abbr title="Card Verification Code">CVC</abbr>.

![](./18-checkout-payment.png)

You have successfully made a purchase from your new marketplace!

## Review the transaction on Stripe

Your stint pretending to the be the customer is over—you can now return to your marketplace business-runner persona.

Let’s switch over to your platform’s Stripe dashboard, to see how this transaction appears.

You can see the payment went through on Stripe:

![](./19-stripe-transaction.png)

This transaction has the metadata that Commerce includes automatically, like the order ID and order number, making it easy to find the corresponding order in Craft Commerce.

It also includes <cite>transaction group</cite>, which indicates payment splitting has occurred.

At the time of writing, this ID isn’t a clickable link in the Stripe dashboard, but you can copy it and search it. This will take you to a different view showing all the details you need about this group of transactions:

![](./20-stripe-transaction-group.png)

Let’s check the payment splitting has occurred as we expect.

The original order was for one $10 coffee and one $12 coffee. The marketplace we’re building took a 10% fee on each of them. So the result should be:

<table>
<thead>
<tr>
<th>Payee</th>
<th>Amount</th>
</tr>
</thead>
<tbody>
<tr>
<td>Marketplace (via fee)</td><td>$2.20</td>
</tr>
<tr>
<td>Roaster 1</td><td>$9.00</td>
</tr>
<tr>
<td>Roaster 2</td><td>$10.80</td>
</tr>
</tbody>
<tfoot>
<tr>
<th>Total</th><td>$22.00</td>
</tr>
</tfoot>
</table>

We see that reflected in the Stripe results.

The Stripe account has kept $2.20, and a transfer has been made to one account for $9.00, and another account for $10.80. The `acct_` IDs that are referenced are the same ones you’ll see if you visit the entries for Roaster 1 and Roaster 2.

We can even go back and login as Roaster 1 again—imagining we want to check on our payouts ourselves—and login to our own Stripe Express dashboard. It will show us that we have one $9.00 payout pending.

<!--

![](https://picsum.photos/id/13/2500/1667)

-->

{/*

## What’s next

What’s next, ie. stuff that isn’t going to be in this tutorial

Maybe don’t even include product management yet? Or only the very most basic stuff?

For customers
- Add individual vendor pages, ex. `/roasters/example-roaster`, which lists all the products from a single roaster

For vendors
- Create and edit products
- Advanced handling of permissions, based on their roaster instead of technically being able to edit any entry
- Vendor user registration
  - Could allow public sign up by roasters, likely with a pending approval step
  - Once the roaster has successfully signed up, you could allow them to register new users within that
- Could do fixed product, multiple vendors. Ex. Maybe this marketplace only sells medium roasts, so you have a single medium roast product that every roaster can edit, and they don’t create new products.

For administrators

*/}
