# Release Notes for Marketplace

## Unreleased

> [!NOTE]
> [Please read the upgrade guide before upgrading Marketplace.](https://craft-marketplace.kennethormandy.com/docs/upgrading-from-v1) The changelog documents all public API changes, but for most use cases only a few changes from the upgrade guide are required.

## Added
- Added support for new Stripe account creation, without OAuth
- Added Client ID setting (final name TBD)
- Added ability to preview account IDs in element tables
- Added AccountAccess event, to allow custom verification logic before creating Stripe account links
- Added `craft.marketplace` Twig helpers
- Added `craft.marketplace.renderHostedOnboarding()` Twig helper
- Added `craft.marketplace.renderHostedDashboard()` Twig helper
- Added combined `craft.marketplace.renderConnector()` Twig helper
- Added `getAccount()` and `isConnected()` to Accounts service
- Added `getAccountId()` and `getIsConnected()` to elements with a Marketplace field
- Added new a `marketplace/accounts/replace` and `remove` console commands for, ex. moving from Stripe Live mode in production to Test mode locally

## Changed
- Changed the the Payee field type to be opt-in via the `usePayeeFieldType` setting, in favour of determining the payee in the `EVENT_AFTER_DETERMINE_PAYEE` event
- Updates Stripe from v7.x to v10.x to match the Commerce Stripe gateway
- Renamed `kennethormandy\marketplace\services\AccountsService` → `kennethormandy\marketplace\services\Accounts`
- Renamed `kennethormandy\marketplace\services\FeesService` → `kennethormandy\marketplace\services\Fees`
- Renamed `kennethormandy\marketplace\services\HandlesService` → `kennethormandy\marketplace\services\Handles`
- Renamed `kennethormandy\marketplace\services\PayeesService` → `kennethormandy\marketplace\services\Payees`
- Renamed `handlesService` to `handles`
- `Marketplace::getInstance()->accounts->createLoginLink($accountId, $params)` requires a Stripe account ID as the first argument, which was effectively already the case.
- `Marketplace::getInstance()->accounts->createAccountLink($accountId, $params)` has been added, which may be what you’d prefer if you were using `createLoginLink()`
- When creating an account link for a non-User element, the User will now need to be related to the element. This behaviour can be overridden in the AccountAccess event
- Changed `$gatewayAccountId` to just `$accountId` in the `PayeeEvent`
- Changed `getGatewayAccountId` to just `getAccountId` in the `Payees` service

## Removed
- OAuth Client removed as a dependency
- Removed unused provider for standards Stripe accounts (only Express accounts were and are supported)
- Removed internal references to potentially supporting direct charges; Marketplace is only going to support destination changes for the forseeable future.
- Removed now non-existent app handle from settings, and `getAppHandle()` from handles service. Unlike OAuth Client, the underlying auth plugin doesn’t have a concept of “apps.”
- Removed display of orders by a payee on a payee’s user page
- Removed support for configuring fees visually via the settings in the control panel
- Removed `marketplace/accounts/create-logout-link` action, which has no role in new account flow

## Fixed
- Fixed internal name of 

***

## v1.6.0 (never tagged?)

### Added
- Added support for connecting accounts to any Element, not just Users

### Changed
- When a User connects a new account, the account ID returned by the platform is saved in the `EVENT_AFTER_AUTHENTICATE` step, instead of the `EVENT_CREATE_TOKEN_MODEL_FROM_RESPONSE` step. There should be no difference in user experience, from an end user’s perspective.

### Fixed
- Fixed a presumptuous check that assumed `'stripeConnect'` could always be a fallback field handle for a Stripe Connect Button, when it actually probably means there is no Stripe Connect Button field
- Fixed incorrect `log` argument during redirect, after authenticating with OAuth

## v1.5.1 - 2021-04-26

### Fixed
- Fixed inconsistent saving of ID in Payee field, which also fixed the Payee field value not showing in admin templates after resaving all Products via console commands

## v1.5.0 - 2021-03-29

### Added
- Added support for creating login links from the front-end, via `marketplace/accounts/create-login-link` action
- Added support for a custom redirect upon leaving or logging out from the external Stripe Connect Express dashboard
- Added account and dashboard related tests

## v1.4.1 - 2021-03-19

### Fixed
- Fixed `EVENT_AFTER_DETERMINE_PAYEE` not firing, when no default Payee was set on Product in Craft CMS admin

## v1.4.0 - 2021-03-15

### Added
- Added support for Craft Commerce’s “Authorize Only (Manual Capture)” [`capture`](https://craftcms.com/docs/commerce/3.x/transactions.html#capture) transactions
- Added more helpful error for soft-deleted fee or name conflicts [#16](https://github.com/kennethormandy/craft-marketplace/issues/16)

## v1.3.1 - 2021-03-10

### Fixed
- Fixed Fee Type select field not having a default when editing

## v1.3.0 - 2021-03-05

### Added
- Added dedicated plugin log file

### Fixed
- Fixed handle validation for fees, which also avoids a possible 404 error while editing the fee [#11](https://github.com/kennethormandy/craft-marketplace/issues/11)
- Fixed possible issue when re-saving product that already had a saved Payee field
- Fixed hash being required as part of redirect link, for redirect after auth

## v1.2.0 - 2021-02-15

### Added
- Added initial support for Craft Commerce Pro and multiple line items, when all line items have the same payee

## v1.1.0 - 2021-01-16

### Added
- Added `PayeesServices` [and initial developer events](https://github.com/kennethormandy/craft-marketplace/tree/ko-payees-service#events), with `EVENT_BEFORE_DETERMINE_PAYEE` and `EVENT_AFTER_DETERMINE_PAYEE`, [#12](https://github.com/kennethormandy/craft-marketplace/issues/12)

### Removed
- Removed unused imports

## v1.0.2 - 2020-11-13

### Fixed
- Fixed install of `oauth2-stripe` dependency, replacing forked dependency with published package

## v1.0.0 - 2020-11-13

- Initial public release

### Added
- Added new README, images, and plugin icon
- Added testing framework
- Added latest changes from Craft Commerce to example template
- Added Stripe PHP v7 to allowed version range, for latest Stripe Payment Gateway plugin

### Fixed
- Fixed use of connection button from OAuth plugin settings page

## v0.6.0 - 2020-08-30

### Added
- Added ability to customize Fees
- Added `buy.html` example template, with as few modifications as possible
- Added [Craft License](https://github.com/kennethormandy/craft-marketplace/blob/main/LICENSE.md) file

## v0.5.0 - 2020-06-04

### Added 
- Added Lite edition config
- Added support for Digital Products on Craft v3.4 and Craft Commerce v3

### Fixed
- Fixed check when no orders or line items exist
- Fixed fallback to default Stripe account on Craft Commerce v3
- Fixed Stripe OAuth dependency install

## 0.4.0 - 2020-04-06

### Added
- Added demo of listing payee on default order page
- Added support for selecting the OAuth app to use
- Added dynamic redirect to current view after auth

### Fixed
- Fixed date paid showing as current date if order had been refunded
- The OAuth app handle no longer needs to be called `stripe` specifically

## 0.3.0 - 2020-02-07

### Added
- Added proper saving of token without forked dependencies
- Added distinct Stripe Connect and Stripe Connect Express providers
- Added error message for missing Stripe API key

### Fixed
- Fixed saving of user on Payee field

### Changed
- Moved into stand-alone repository

## 0.2.0 - 2020-01-10

### Added
- Added settings panel

### Changed
- Changed display of order listing based on Commerce permissions

## 0.1.0 - 2019-12-06

- Initial private release
