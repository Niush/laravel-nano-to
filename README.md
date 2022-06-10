# Laravel NANO.TO

Easily integrate [Nano.to](https://nano.to/) Payment Gateway in Laravel Application, with full control.

[![Latest Stable Version](https://poser.pugx.org/niush/laravel-nano-to/v)](https://packagist.org/packages/niush/laravel-nano-to)
[![Total Downloads](https://poser.pugx.org/niush/laravel-nano-to/downloads)](https://packagist.org/packages/niush/laravel-nano-to)
[![PHP Version Require](https://poser.pugx.org/niush/laravel-nano-to/require/php)](https://packagist.org/packages/niush/laravel-nano-to)
![GitHub Actions](https://github.com/niush/laravel-nano-to/actions/workflows/main.yml/badge.svg)

## Installation on Laravel

You can install the package via composer:

```bash
composer require niush/laravel-nano-to
```

Then publish the config file using artisan.
```bash
php artisan vendor:publish --provider="Niush\NanoTo\NanoToServiceProvider"
```

Update config file (``config/nano-to.php``) with your desired settings. Use ``env`` where ever required.

## Configuration
Add Nano Webhook Secret in your env file. Make sure it is difficult to guess with random string. **DO NOT USE SAME AS APP_ENV**.
```
NANO_WEBHOOK_SECRET=
```

Go through the generated config file and update as required. Make sure the Nano address all belongs to you and is accessible. You can provide, default title and description.

## Required Named Routes
For this to work properly, you must have these 3 named routes created to handle your business logic. **The named route can always be customized in config file**.
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
return NanoTo::amount(10)->create($order->id, function($checkout_url) {
    // Do Something with $checkout_url if required.
    // For SPA send link as JSON response etc.
})->send();

// 2) With Custom Info (Else uses title and description from config)
return NanoTo::info("Payment for Subscription", "<i>Also accepts HTML</i>")
->amount(9.99)
->create($order->id)->send();

// 3) With Additional Metadata (Can be received in Webhook)
return NanoTo::amount(9.99)->metadata([
    "user_id" => $user->id,
    "order_id" => $order->id
])->create($order->id)->send();

// 4) For Suggest based payment. Useful in cases like Donation.
return NanoTo::info("Donate Us")
->suggest([
    ["name" => "Coffee", "price" => "10"],
    ["name" => "Meal", "price" => "50"] 
])
->create($uuid)->send();

// 5) Use RAW friendly Amount in QR Codes (e.g. for Natrium)
return NanoTo::asRaw()->amount(9.99)->create($order->id)->send();

// 6) With Custom Image in Checkout page
return NanoTo::withImage("full_url_of_image")->amount(9.99)->create($order->id)->send();

// 7) Or Simply, if no need to track anything. And, required routes do not need {id} param.
return NanoTo::create();

// Receiving Nano Address will randomly be picked from config file.
// The first parameter of create (e.g. $order->id) will be used as params in named routes. 
```

**You might want to use custom Webhook Secret. So that, it is always different for each checkout. So, instead of using same environment variable. You can do:**
```php
return NanoTo::amount(10)->secret(
    config("nano-to.webhook_secret") . $order->secret_id . $user->id
)->create($order->id)->send();
```


## Webhook Response Example
```js
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
$request->header('Webhook-Secret') == config("nano-to.webhook_secret") // Valid
```

```js
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
    "value": "0.01",
    "raw": false
  },
  "plan": { // If using Suggest mode
    "price": 10,
    "name": "Meal"
  },
  "block": { // Block Information
      "type": "receive", // You must be receiving :)
      "representative": "nano_3chart...",
      "balance": "3.726479",
      "previous": "1922BFA40E86C....",
      "account": "nano_36qn7ydq...", // Sender Address
      "amount": "1.788115", // Nano Received
      "height": "37",
      "hash": "9829B0306E5269A9A0...", // Transaction Identifier (Most important piece to store.)
      "work": "210862fa...",
      "timestamp": "16319544..",
      "amount_raw": "1788115000000000000..." // RAW Nano
      "balance_raw": "372647920414...",
      "from": "nano_36qn7ydq..."
      "to": "nano_3gxhq..."
      "message": ""
  },
  "metadata": { // All Additional Metadata sent
    "user_id": "my_meta"
  }
}
```
```php
// Compare the body, store required info in DB and finally update the order status. In Webhook Controller.
$request->input('amount') == $order->amount_in_usd;
$request->input('status') == "complete";
$request->input('block.type') == "receive";
// You can also compare receiver address is in config or not.

$order->via = "nano";
$order->hash = $request->input('block.hash');
$order->status = "complete";
$order-save();
```

### Full Example of Webhook Controller
<details>
  <summary>Click to expand!</summary>
  
```php
public function webhook(Request $request, Order $order) {
    if($request->header('Webhook-Secret') != config("nano-to.webhook_secret")) {
        $order->status = "failed"; // Webhook Secret is MALFORMED
        $order->remarks = "Payment Verification Malformed";
    }
    else {
        if(
            $request->input('amount') == $order->amount_in_usd &&
            $request->input('status') == "complete" &&
            $request->input('block.type') == "receive" &&
            $request->input('block.hash')
        ) {
            $order->status = "complete";
            $order->hash = $request->input('block.hash');
            $order->remarks = "Payment Complete from Address: " . $request->input('block.account') . " , with Amount: " . $request->input('method.amount');
            $order->save();
        }
        else {
            $order->status = "failed"; // Payment Amount is not correct or not complete etc.
            $order->remarks = "Payment Was Not Fully Completed";
        }
    }

    // You can also utilize Metadata for verification:
    // $request->input('metadata.user_id') == $order->user_id;

    $order->save();

    return ["success" => true];
}
```
</details>

<span id="advanced-usage"></span>
### Advanced Usage (API / Helpers)
[View details and response here](https://docs.nano.to/guide/developer-api)

```php
use Niush\NanoTo\NanoToApi;

// 1) Get CoinMarketCap conversion rate
NanoToApi::getPrice("NANO", "USD");
NanoToApi::getPrice("XMR", "NPR");
NanoToApi::getPrice("NANO", "XMR");

// 2) Get Nano.to Custom Username alias information
NanoToApi::getUsername("moon");

// 3) Get Nano Address Information (OR Nano.to alias info)
NanoToApi::getNanoAddressInfo("nano_3xxxx");

// 4) Get Total Nano Balance from all nano address provided in config file
NanoToApi::getTotalNanoBalance();

// 5) Get Pending Nano Blocks
NanoToApi::getPendingNanoBlocks("nano_3xxxx");

// 6) Get Last 50+ Block History
NanoToApi::getNanoAddressHistory("nano_3xxxx");

// 7) Get Nano Transaction by specific Amount (Amount must be in Nano decimal format)
NanoToApi::getNanoTransactionByAmount("nano_3xxxx", "2.101");

// 8) Get Nano Transaction by block HASH
NanoToApi::getNanoTransactionByHash("NANO_HASH");

// 9) Get JSON Representation of given checkout URL. Only has 12 hour lifespan.
NanoToApi::getCheckoutUrlAsJson("https://nano.to/checkout/xxx");

// 10) Get List Of Public Representatives for Nano. And, Search by first parameter.
NanoToApi::getListOfPublicRepresentatives("ninja");

// 11) Get List Of Nano.to known Usernames. And, Search by first parameter.
NanoToApi::getListOfNanoUsernames("esteban");

// 12) Check if nanocrawler is down or unreachable. Returns boolean true if down.
NanoToApi::isNanoCrawlerDown();

// 13) Check if Nano.to base_url is down or unreachable. Returns boolean true if down.
NanoToApi::isNanoToDown();
```

### Translation
Add translation for these messages if required.
- `nano-to.checkout-page-not-loaded` = "Unable to load Checkout Page."
- `nano-to.no-receiver` = "Receiver Account was not available."


### Testing
Contributions are welcome.
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
