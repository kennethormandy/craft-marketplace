---
sidebar_position: 1
---

# Tutorial Intro

Let's discover **Docusaurus in less than 5 minutes**.

```twig
{% embed '_tip.twig' with {
    this: 'that',
} %}
  {% block content %}
    <p>I’m a helpful tip!</p>
  {% endblock %}
{% endembed %}
```

```twig
{% set result = false %}

{% embed 'file' with {
    this: that
} %}

{% endembed %}


{%- if result -%}
This and that
{% endif %}

{#
This is a comment
#}
<h1>{{ header|title }}</h1>

{# This is a comment #}

<p>Hello</p>

{#This and #}

```

```php
/**
 * @see https://example.com
 * @param $this - That
 */
Event::on(
    FeesService::class,
    FeesService::EVENT_AFTER_CALCULATE_FEES_AMOUNT,
    function (FeesEvent $event) {
        $order = $event->order;
        $lineItems = $order->lineItems;

        // Example conditional. Check something in the
        // product snapshot, and change the fee accordingly.
        if (
            $lineItems[0] &&
            $lineItems[0]->snapshot['title'] === 'My specific product'
        ) {
            // Overwrite the total calculated fee amount on the event.
            // This amount is set as an integer in “cents,” so in this case
            // the Fee will be US$12.34 on a platform using US dollars.
            $event->amount = 1234;
        }

        // In all other cases, the fee would be calculated using your
        // existing global fee settings.
    }
);
```

## Getting Started

Get started by **creating a new site**.

Or **try Docusaurus immediately** with **[docusaurus.new](https://docusaurus.new)**.

### What you'll need

- [Node.js](https://nodejs.org/en/download/) version 18.0 or above:
  - When installing Node.js, you are recommended to check all checkboxes related to dependencies.

## Generate a new site

Generate a new Docusaurus site using the **classic template**.

The classic template will automatically be added to your project after you run the command:

```bash
npm init docusaurus@latest docs classic
```

You can type this command into Command Prompt, Powershell, Terminal, or any other integrated terminal of your code editor.

The command also installs all necessary dependencies you need to run Docusaurus.

## Start your site

Run the development server:

```bash
cd docs
npm run start
```

The `cd` command changes the directory you're working with. In order to work with your newly created Docusaurus site, you'll need to navigate the terminal there.

The `npm run start` command builds your website locally and serves it through a development server, ready for you to view at http://localhost:3000/.

Open `docs/intro.md` (this page) and edit some lines: the site **reloads automatically** and displays your changes.
