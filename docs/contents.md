<!-- In progress -->

## Root

Index (full homepage, not just docs)
About (typical /about page)
Blog

## Docs

### Install
- Install
- Editions (cover how Lite pairs with Craft Commerce Lite, and Pro with Pro)

### Configure
- Create a Stripe Connect app
- OAuth 2.0 Client
- Redirect from Stripe to Craft CMS
- Configure Marketplace for Craft Commerce
- Add Marketplace fields

### General use
- Add a new user
- Add the User as a Payee on a Product
- Add a Fee for your platform

### Template
- Customize the Buy Template

### Fields
- MarketplaceConnectButton
- MarketplacePayee

### Objects (Concepts?)

- OAuth Apps (OAuth plugin)
- Gateway Accounts
- Payees
- [Fees](fees)

### Guides
- How to setup Marketplace, all on one page [Possible to pull in start-to-finish guide as includes from other pages?]
- How to enable front-end user accounts

## Developer? API reference?

(This will live on our docs, but for Craft it lives in Craftnet)

- Events
  - PayeesService Events `kennethormandy\marketplace\services\PayeesService`
    - `PayeesService::EVENT_BEFORE_DETERMINE_PAYEE`
    - `PayeesService::EVENT_AFTER_DETERMINE_PAYEE`
  - FeesService Events `kennethormandy\marketplace\services\FeesService`
    - `FeesService::EVENT_BEFORE_CALCULATE_FEES_AMOUNT`
    - `FeesService::EVENT_AFTER_CALCULATE_FEES_AMOUNT`
- Services (might not need docs yet, less important than events)
  - `AccountsService`
  - `FeesService`
  - `HandlesService` (Leave undocumented?)
  - `PayeesService`
- Providers
  - Stripe Connect Express
  - Stripe Connect (Standard)
  - Add an issue if you want to support other providers (ex. Braintree)
