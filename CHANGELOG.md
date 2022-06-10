# Changelog

All notable changes to `laravel-nano-to` will be documented in this file

## 1.1.3 - 2022-06-10

- Dependencies Update
- Fixed Tests due to updated real APIs in Nano.to

## 1.1.2 - 2022-03-30

- Dependencies Update & Fixes

## 1.1.1 - 2022-02-13

- Laravel 9 Support
- Dependencies Update & Fixes

## 1.1.0 - 2021-12-22

- Update includes breaking changes, but easily fixable.
- Change Facade name from 'LaravelNanoTo' to 'NanoTo' for simplicity.
- Change Namespace from 'LaravelNanoTo' to 'NanoTo' for simplicity. Package name stays same.
- Renamed config file from 'laravel-nano-to' to 'nano-to' for simplicity.
- Updated Service Provider, Tests and Documentation accordingly.

## 1.0.5 - 2021-12-22

- Nano.to API to get Public Representatives
- Nano.to API to get all known Usernames

## 1.0.4 - 2021-12-03

- Use config base_url for choosing nano.to api url
- Clean Up LaravelNanoTo Facade to not use fake response, instead utilize Guzzle Client Mock in tests
- Removed Nano.to GET based function & tests
- isNanoToDown() helper function

## 1.0.3 - 2021-11-22

- Some Nano.to API route were changed. It has been updated.
- NanoToApi now returns Object or Collection as required and includes Doc Blocks
- Functions to Get Nano block info by Hash, Get Checkout URLs JSON representation, Check if Nano Crawler is down
- Use new comma separated format of background and color config, with function to override it
- Function to add Image to checkout page
- Code Formatting & Cleaning

## 1.0.2 - 2021-10-18

- Use Nano.to POST Method as default checkout page generator
- Ability to add additional Metadata when creating checkout page (Can be received back in Webhook)
- Added Business (Name, Logo, Favicon), Background & Color customization
- Ability to generate QR Code for RAW Nano (Supporting Natrium)
- NanoToApi Advanced / Helper functions
- PHP Unit Tests Updated & Added with multiple Issue Fixes

## 1.0.1 - 2021-09-19

- Allow Custom Webhook Secret
- PHP Unit Tests Added & Issue Fixes

## 1.0.0 - 2021-09-18

- Initial release
- Simple Nano.to gateway initializer with proper README.md Documentation
