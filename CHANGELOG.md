# Release Notes for Marketplace

## Unreleased

### Added
- Added demo of listing payee on default order page
- Added support for selecting the OAuth app to use
- Added dynamic redirect to current view after auth

### Fixed
- Fixed date paid showing as current date if order had been refunded
- The OAuth app handle no longer needs to be called `stripe` specifically

## 0.3.0 - 2019-02-07

### Added
- Added proper saving of token without forked dependencies
- Added distinct Stripe Connect and Stripe Connect Express providers
- Added error message for missing Stripe API key

### Fixed
- Fixed saving of user on Payee field

### Changed
- Moved into stand-alone repository

## 0.2.0 - 2019-01-10

### Added
- Added settings panel

### Changed
- Changed display of order listing based on Commerce permissions

## 0.1.0 - 2019-12-06

- Initial release
