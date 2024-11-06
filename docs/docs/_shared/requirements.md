import Tabs from '@theme/Tabs'
import TabItem from '@theme/TabItem'

### Craft Commerce

Install Commerce:

<Tabs>
<TabItem value="ddev" label="DDEV">

```sh
ddev composer require craftcms/commerce -w
ddev craft plugin/install commerce
```

</TabItem>
<TabItem value="shell" label="Shell">

```sh
composer require craftcms/commerce -w
php craft plugin/install commerce
```

</TabItem>
</Tabs>

### Stripe for Craft Commerce

Install Stripe for Craft Commerce:

<Tabs>
<TabItem value="ddev" label="DDEV">

```sh
ddev composer require craftcms/commerce-stripe -w
ddev craft plugin/install commerce-stripe
```

</TabItem>
<TabItem value="shell" label="Shell">

```sh
composer require craftcms/commerce-stripe -w
php craft plugin/install commerce-stripe
```

</TabItem>
</Tabs>

### Marketplace

Install Marketplace:

<Tabs>
<TabItem value="ddev" label="DDEV">

```sh
ddev composer require kennethormandy/craft-marketplace -w
ddev craft plugin/install marketplace
```

</TabItem>
<TabItem value="shell" label="Shell">

```sh
composer require kennethormandy/craft-marketplace -w
php craft plugin/install marketplace
```

</TabItem>
</Tabs>
