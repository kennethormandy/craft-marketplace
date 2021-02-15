# Release Notes for Marketplace

## Unreleased

- Adds initial support for Craft Commerce Pro and multiple line items, when all line items have the same payee

## v1.1.0 - 2021-01-16

- Adds `PayeesServices` [and initial developer events](https://github.com/kennethormandy/craft-marketplace/tree/ko-payees-service#events), with `EVENT_BEFORE_DETERMINE_PAYEE` and `EVENT_AFTER_DETERMINE_PAYEE`, [#12](https://github.com/kennethormandy/craft-marketplace/issues/12)
- Removed unused imports

## v1.0.2 - 2020-11-13

### Fixed
- Fixed install of `oauth2-stripe` dependency, replacing forked dependency with published package

## v1.0.0 - 2020-11-13

### Added
- Adds new README, images, and plugin icon
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

- Initial release
