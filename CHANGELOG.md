# Release Notes for Marketplace

## Unreleased

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
