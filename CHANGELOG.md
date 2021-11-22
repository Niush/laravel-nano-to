# Changelog

All notable changes to `laravel-nano-to` will be documented in this file

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
