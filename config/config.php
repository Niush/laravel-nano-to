<?php

/*
 * You can place your custom package configuration in here.
 */
return [
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
     * Address that receives certain currencies.
     * One of these is chosen randomly per symbol used.
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
     * Named Route to Re-direct when Nano payment is successful. e.g. /order/success/{id}
     * If Named Route not found, it will use the string as full url itself. Useful for sending to different domain etc.
     */
    'success_url' => 'nano-to-success',

    /**
     * Named Route to Re-direct when Nano payment is cancelled.
     * If Named Route not found, it will use the string as full url itself. Useful for sending to different domain etc.
     */
    'cancel_url' => 'nano-to-cancel',

    /**
     * Named Route for Webhook to update the order status.
     * If Named Route not found, it will use the string as full url itself. Useful for sending to different domain etc.
     */
    'webhook_url' => 'nano-to-webhook',

    /**
     * For non production test, you might want to use web based webhook inspector like; octohook, posthook etc.
     * It will automatically be used, if environment is not production.
     */
    'local_webhook_url' => '', // https://octo.hk/xxxxxx
];
