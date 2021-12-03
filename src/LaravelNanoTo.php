<?php

namespace Niush\LaravelNanoTo;

use Error;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

class LaravelNanoTo
{
    protected $base_url;
    protected $title = "";
    protected $description = "";
    protected $amount;
    protected $suggest;
    protected $business;
    protected $background;
    protected $color;
    protected $checkout_url;
    protected $webhook_secret;
    protected $symbol = 'nano';
    protected $metadata = null;
    protected $raw = false;
    protected $image = null;

    public function __construct()
    {
        $this->base_url = config('laravel-nano-to.base_url', 'https://nano.to');
        $this->title = config('laravel-nano-to.title');
        $this->description = config('laravel-nano-to.description');
        $this->webhook_secret = config('laravel-nano-to.webhook_secret');
        $this->business = config('laravel-nano-to.business');
        $this->background = config('laravel-nano-to.background');
        $this->color = config('laravel-nano-to.color');
    }

    /**
     * Set Info or defaults to config file
     *
     * @params string $title
     * @params string $description
     * @return Niush\LaravelNanoTo\LaravelNanoTo
     */
    public function info($title = null, $description = null)
    {
        $this->title = $title ?? $this->title;
        $this->description = $description ?? $this->description;
        return $this;
    }

    /**
     * Sets the amount in USD. Will Override suggest if also provided.
     *
     * @params string $amount
     * @return Niush\LaravelNanoTo\LaravelNanoTo
     */
    public function amount($amount)
    {
        if ($amount < 0.1) {
            throw new Error("Minimum allowed amount in USD is 0.1");
        }
        $this->amount = $amount;
        return $this;
    }

    /**
     * Sets the suggested name and price. Useful for quick donations.
     *
     * [ ["name" => "Coffee", "price" => "10"], ["name" => "Meal", "price" => "50"] ]
     *
     * @params array $suggest
     * @return Niush\LaravelNanoTo\LaravelNanoTo
     */
    public function suggest($suggest)
    {
        $this->suggest = $suggest;
        return $this;
    }

    /**
     * Override Business name and logo from config
     *
     * ["name"=>"Company Name", "logo"=>"https://logo.png", "favicon"=>"https://logo.ico"]
     *
     * @params array $business
     * @return Niush\LaravelNanoTo\LaravelNanoTo
     */
    public function business($business)
    {
        $this->business = $business;
        return $this;
    }

    /**
     * Override Background color
     *
     * @params string $background
     * @return Niush\LaravelNanoTo\LaravelNanoTo
     */
    public function background($background)
    {
        $this->background = $background;
        return $this;
    }

    /**
     * Override Foreground color
     *
     * @params string $color
     * @return Niush\LaravelNanoTo\LaravelNanoTo
     */
    public function color($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Add Single Image to Checkout page. Provide full url.
     *
     * @params string $image_url
     * @return Niush\LaravelNanoTo\LaravelNanoTo
     */
    public function withImage($image_url)
    {
        $this->image = $image_url;
        return $this;
    }

    /**
     * Use Custom Webhook Secret
     *
     * @params string $secret
     * @return Niush\LaravelNanoTo\LaravelNanoTo
     */
    public function secret($secret)
    {
        $this->webhook_secret = strval($secret);
        return $this;
    }

    /**
     * Set the additional Metadata in request body
     *
     * @params array $metadata
     * @return Niush\LaravelNanoTo\LaravelNanoTo
     */
    public function metadata($metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Generate RAW friendly QR Codes (e.g. for Natrium)
     *
     * @return Niush\LaravelNanoTo\LaravelNanoTo
     */
    public function asRaw()
    {
        $this->raw = true;
        return $this;
    }

    /**
     * Get the Nano.to Gateway URL.
     *
     * @params integer|string $unique_id
     * @params function $callback
     * @return Niush\LaravelNanoTo\LaravelNanoTo || Illuminate\Http\RedirectResponse
     */
    public function create($unique_id = null, $callback = null)
    {
        $accounts = config('laravel-nano-to.accounts.' . $this->symbol, []);
        $success_url = Route::has(config('laravel-nano-to.success_url'))
        ? route(config('laravel-nano-to.success_url'), $unique_id)
        : config('laravel-nano-to.success_url');

        $cancel_url = Route::has(config('laravel-nano-to.cancel_url'))
        ? route(config('laravel-nano-to.cancel_url'), $unique_id)
        : config('laravel-nano-to.cancel_url');

        $webhook_url = Route::has(config('laravel-nano-to.webhook_url'))
        ? route(config('laravel-nano-to.webhook_url'), $unique_id)
        : config('laravel-nano-to.webhook_url');

        // If Local use local_webhook_url from config instead.
        if (!App::environment(['production', 'prod']) && config('laravel-nano-to.local_webhook_url')) {
            $webhook_url = config('laravel-nano-to.local_webhook_url');
        }

        if ($accounts && sizeof($accounts) > 0) {
            $address = $accounts[array_rand($accounts)];
            $url = $this->base_url . '/' . $address;
            $body = [
                "title" => $this->title,
                "description" => $this->description,
                "success_url" => $success_url,
                "cancel_url" => $cancel_url,
                "webhook_url" => $webhook_url,
                "webhook_secret" => $this->webhook_secret,
                "background" => $this->background,
                "color" => $this->color,
                "raw" => $this->raw,
            ];

            if ($this->amount) {
                $body['price'] = $this->amount;
            } elseif ($this->suggest) {
                $body['plans'] = $this->suggest;
            }

            if ($this->business) {
                $body['business'] = $this->business;
            }

            if ($this->metadata) {
                $body['metadata'] = $this->metadata;
            }

            if ($this->image) {
                $body['image'] = $this->image;
            }

            try {
                $client = app(Client::class);

                $response = $client->post($url, [
                    'json' => $body,
                ]);
                // var_dump($response->getBody()->getContents());
                $this->checkout_url = json_decode($response->getBody()->getContents(), true)["url"];

                if (!$this->checkout_url) {
                    return $this->throw_checkout_page_not_loaded();
                } else {
                    if ($callback) {
                        if (App::environment() == 'testing') {
                            $callback($this->checkout_url, $body);
                        } else {
                            $callback($this->checkout_url);
                        }
                    } else {
                        if (!$unique_id) {
                            return $this->send();
                        }
                    }

                    return $this;
                }
            } catch (\Exception$e) {
                return $this->throw_checkout_page_not_loaded($e);
            }
        } else {
            if (\Lang::has('nano-to.no-receiver')) {
                throw new Error(__("nano-to.no-receiver"));
            } else {
                throw new Error("Receiver Account was not available.");
            }
        }
    }

    /**
     * Redirect to Nano.to checkout URL
     *
     * @return Niush\LaravelNanoTo\LaravelNanoTo
     */
    public function send()
    {
        return redirect()->to($this->checkout_url);
    }

    public function throw_checkout_page_not_loaded($e = null)
    {
        if ($e) {
            throw $e;
        }
        if (\Lang::has('nano-to.checkout-page-not-loaded')) {
            throw new Error(__("nano-to.checkout-page-not-loaded"));
        } else {
            throw new Error("Unable to load Checkout Page.");
        }
    }
}
