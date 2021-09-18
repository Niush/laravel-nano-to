# Laravel NANO.TO

Easily integrate [Nano.to](https://nano.to/) Payment Gateway in Laravel Application, with full control.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/niush/laravel-nano-to.svg?style=flat-square)](https://packagist.org/packages/niush/laravel-nano-to)
[![Total Downloads](https://img.shields.io/packagist/dt/niush/laravel-nano-to.svg?style=flat-square)](https://packagist.org/packages/niush/laravel-nano-to)
![GitHub Actions](https://github.com/niush/laravel-nano-to/actions/workflows/main.yml/badge.svg)

## Installation on Laravel

You can install the package via composer:

```bash
composer require niush/laravel-nano-to
```

Then publish the config file using artisan.
```bash
php artisan vendor:publish --provider="Niush\LaravelNanoTo\LaravelNanoToServiceProvider"
```

Update config file (``config/laravel-nano-to.php``) with your desired settings. Use ``env`` where ever required.

## Configuration
Add Nano Webhook Secret in your env file. Make sure it is difficult to guess with random string. **DO NOT USE APP_ENV**.
```
NANO_WEBHOOK_SECRET=
```

Go through the generated config file and update as required. Make sure the Nano address all belongs to you and is accessible. You can provide, default title and description.

## Required Named Routes
For this to work properly, you must have these 3 named routes created to handle your business logic.
- `nano-to-success`: Redirected when Payment is successful. Do not confirm the payment using this route. The confirmation must be handled in webhook url.
    ```php
    Route::get('/order/success/{id}', [OrderController::class, 'success'])->name('nano-to-success'); // E.g. Shows a static order success page.
    ```

- `nano-to-cancel`: Redirected when Payment is canceled, using back button. Useful to redirect back to cart page etc.
    ```php
    Route::get('/order/cancel/{id}', [OrderController::class, 'cancel'])->name('nano-to-cancel'); // E.g. Redirects back to Cart Page
    ```

- `nano-to-webhook`: The POST route that is called by Nano.to when the payment is successfully processed. The request contains payment information, hash, webhook secret in headers. More information can be found down below.
    ```php
    // PERFORM PAYMENT CONFIRMED BUSINESS LOGIC
    Route::post('/order/webhook/{id}', [OrderController::class, 'webhook'])->name('nano-to-webhook');
    ```

## Using Full Route Instead:
By default the config accepts named route for webhook and success page. If you want to use full route, for cases like different domain and backend to handle webhook or success page. You can update the config files `success_url`, `cancel_url` and `webhook_url` to be a full URL with domain name.

As, you have full control, it can easily be implemented via APIs also.

## Usage
**NOTE: Amount is always in USD Currency.**

For initiating Payment process: 

```php
// 1) With Specific Amount
return LaravelNanoTo::amount(10)->create($order->id, function($checkout_url) {
    // Do Something with $checkout_url if required.
})->send();

// 2) With Custom Info (Else uses title and description from config)
return LaravelNanoTo::info("Payment for Subscription", "<i>Also accepts HTML</i>")
->amount(9.99)
->create($order->id)->send();

// 3) For Suggest based payment. Useful in cases like Donation.
return LaravelNanoTo::info("Donate Us")
->suggest([
    ["name" => "Coffee", "amount" => "10"],
    ["name" => "Meal", "amount" => "50"] 
])
->create($uuid)->send();

// 4) Or Simply, if no need to track anything. And, required routes do not need {id} param.
return LaravelNanoTo::create();

// Receiving Nano Address will randomly be picked from config file.
```

## Webhook Response Example
```json
// Request Headers
{
  "Accept": "application/json, text/plain, */*",
  "Connection": "close",
  "Content-Length": "1005",
  "Content-Type": "application/json",
  "Webhook-Secret": "XXXXXXXXXXXXXXXX"
}
```
```php
// You can get the Header and compare with your config secret. In Webhook Controller:
$request->header('Webhook-Secret') == config("laravel-nano-to.webhook_secret") // Valid
```

```json
// Request Body (JSON)
{
  "id": "ffceexxxxxx", // Transaction ID of Nano.to
  "status": "complete", // Status must be complete
  "amount": "10", // Amount in USD
  "method": {
    "symbol": "nano", // Crypto Currency Used
    "address": "nano_3gxhq...", // Receiving Address
    "name": "Nano",
    "rate": "5.589960", // Currency Rate (Nano → USD)
    "amount": "1.788115", // Nano Received
    "value": "0.01"
  },
  "metadata": {
    "payment": { // Block Information
      "type": "state",
      "representative": "nano_3chart...",
      "link": "391D8B81DB...",
      "balance": "372647920414...",
      "previous": "1922BFA40E86C....",
      "subtype": "receive", // You must be receiving :)
      "account": "nano_36qn7ydq...", // Sender Address
      "amount": "1788115000000000000...", // RAW Nano
      "local_timestamp": "1631954...",
      "height": "37",
      "hash": "9829B0306E5269A9A0...", // Transaction Identifier (Most important piece to store.)
      "work": "210862fa...",
      "signature": "CC16D6519C1113767EA36..",
      "timestamp": "16319544.."
    }
  }
}
```
```php
// Compare the body, store required info in DB and finally update the order status.
$request->input('amount') == $order->amount_in_usd;
$request->input('status') == "complete";
$request->input('metadata.payment.subtype') == "receive";
// You can also compare receiver address is in config or not.

$order->via = "nano";
$order->hash = $request->input('metadata.payment.hash');
$order->status = "complete";
$order-save();
```


### Translation
Add translation for these messages if required.
- `nano-to.checkout-page-not-loaded` = "Unable to load Checkout Page."
- `nano-to.no-receiver` = "Receiver Account was not available."


### Testing
WIP. Tests have not been implemented yet. Contributions are welcome.
```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email author instead of using the issue tracker.

## Credits

-   [Niush Sitaula](https://github.com/Niush)
-   [All Contributors](https://github.com/niush/laravel-nano-to/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

Nano.to is a product of Forward Miami, LLC. ⚡

## Help me with Nano?
1) What is Nano?

    [Nano](https://nano.org/) is a Fee-less, Eco-friendly, Instant digital money for the modern world.
1) How to verify or view block information of my transaction?

    https://nanolooker.com/block/{HASH}

## Show Support
> [Send via Nano.to](https://nano.to/nano_378shkx4k3wd5gxmj3xnjwuxtaf9xrehyz7ugakpiemh8arxq8w9a9xniush?title=Support%20Niush%20with%20NANO&cancel_url=http://www.nooooooooooooooo.com/nooo.mp3)

> nano_378shkx4k3wd5gxmj3xnjwuxtaf9xrehyz7ugakpiemh8arxq8w9a9xniush
