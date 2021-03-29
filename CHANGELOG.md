# Release Notes for Marketplace

## Unreleased

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
