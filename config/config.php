<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    /**
     * Nano.to Base URL of choice (without trailing slash)
     */
    'base_url' => "https://nano.to",

    /**
     * Secret Key for Webhook verification
     */
    'webhook_secret' => env('NANO_WEBHOOK_SECRET'),

    /**
     * Provide Allowed Currencies
     * Supports: nano
     */
    'currencies' => ["nano"],

    /**
     * Addresses that receive certain currencies. One of these is chosen randomly per symbol used.
     * You can also use comma separated environment variable like this:
     * 'nano' => array_map('trim', explode(',', env('NANO_ACCOUNTS')))
     */
    'accounts' => [
        'nano' => [
            //
        ]
    ],

    /**
     * Provide a default title
     */
    'title' => 'Order Payment',

    /**
     * Provide a default description. Supports HTML.
     */
    'description' => '<b>Please make the payment as specified.</b>',

    /**
     * Business Name & Logo (Publicly Accessible Full URL) for customization
     */
    'business' => [
        "name" => env('APP_NAME'),
        "logo" => "",
        "favicon" => ""
    ],

    /**
     * Basic UI Customization
     */
    "background" => "#FFFFFF,#1B9CFC",
    "color" => "black,white",

    /**
     * Named Route to Re-direct when Nano payment is successful. e.g. /order/success/{id}
     * If Named Route not found, it will use the string as full url itself. Useful for sending to different domain etc.
     */
    'success_url' => 'nano-to-success',

    /**
     * Named Route to Re-direct when Nano payment is cancelled. e.g. /order/cancel/{id}
     * If Named Route not found, it will use the string as full url itself. Useful for sending to different domain etc.
     */
    'cancel_url' => 'nano-to-cancel',

    /**
     * Named Route for Webhook to update the order status. e.g. /order/webhook/{id}
     * If Named Route not found, it will use the string as full url itself. Useful for sending to different domain etc.
     */
    'webhook_url' => 'nano-to-webhook',

    /**
     * For non production test, you might want to use web based webhook inspector like; octohook, posthook etc.
     * It will automatically be used, if provided and environment is not production.
     */
    'local_webhook_url' => '', // https://octo.hk/xxxxxx
];
