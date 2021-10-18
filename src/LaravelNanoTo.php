<?php

namespace Niush\LaravelNanoTo;

use Error;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

class LaravelNanoTo
{
    public $base_url = 'https://nano.to';
    public $title = "";
    public $description = "";
    public $amount;
    public $suggest;
    public $business;
    public $checkout_url;
    public $webhook_secret;
    public $symbol = 'nano';
    public $metadata = null;
    public $raw = false;

    public function __construct()
    {
        $this->title = config('laravel-nano-to.title');
        $this->description = config('laravel-nano-to.description');
        $this->webhook_secret = config('laravel-nano-to.webhook_secret');
        $this->business = config('laravel-nano-to.business');
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
        if($amount < 0.1) {
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
        $accounts = config('laravel-nano-to.accounts.'.$this->symbol, []);
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
                "background" => config('laravel-nano-to.background'),
                "color" => config('laravel-nano-to.color'),
                "raw" => $this->raw,
            ];

            if ($this->amount) {
                $body['price'] = $this->amount;
            } elseif ($this->suggest) {
                $body['plans'] = $this->suggest;
            }

            if($this->business) {
                $body['business'] = $this->business;
            }

            if($this->metadata) {
                $body['metadata'] = $this->metadata;
            }

            try {
                $client = new Client();

                // Fake Nano.to response if testing.
                if (App::environment() == 'testing') {
                    $response = new Response(200, [
                        'Content-Type' => 'application/json; charset=utf-8',
                    ], '{"id":"test_id","url":"https://example.com/1","exp":"2021-10-10T01:51:23.853Z"}');
                } else {
                    $response = $client->post($url, [
                        'json' => $body
                    ]);
                }
                // var_dump($response->getBody()->getContents());
                $this->checkout_url = json_decode($response->getBody()->getContents(), true)["url"];

                if (!$this->checkout_url) {
                    return $this->throw_checkout_page_not_loaded();
                } else {
                    if ($callback) {
                        if (App::environment() == 'testing') {
                            $callback($this->checkout_url, $body);
                        }
                        else {
                            $callback($this->checkout_url);
                        }
                    } else {
                        if (!$unique_id) {
                            return $this->send();
                        }
                    }

                    return $this;
                }
            } catch (\Exception $e) {
                if (App::environment() == 'testing') { dd($e); }
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
     * Get the Nano.to Gateway URL.
     * Uses GET method (Default and favorable option is POST)
     *
     * @deprecated Use create function that uses POST action instead. And, has more features.
     *
     * @params integer|string $unique_id
     * @params function $callback
     * @return Niush\LaravelNanoTo\LaravelNanoTo || Illuminate\Http\RedirectResponse
     */
    public function createWithGetRequest($unique_id = null, $callback = null)
    {
        $accounts = config('laravel-nano-to.accounts.'.$this->symbol, []);
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
            $url = $this->base_url .
            '/' . $address .
            '?title=' . $this->title .
            '&description=' . $this->description .
            '&success_url=' . $success_url .
            '&cancel_url=' . $cancel_url .
            '&webhook_url=' . $webhook_url .
            '&webhook_secret=' . $this->webhook_secret .
            '&raw=' . ($this->raw ? 'true' : 'false');

            if ($this->amount) {
                $url .= '&price=' . $this->amount;
            } elseif ($this->suggest) {
                $parameters = array_map(function ($s) {
                    return $s["name"] . ":" . $s["price"];
                }, $this->suggest);

                $this->suggest = implode(",", $parameters);
                $url .= '&suggest=' . $this->suggest;
            }

            try {
                $client = new Client(['allow_redirects' => ['track_redirects' => true]]);

                // Fake Nano.to response if testing.
                if (App::environment() == 'testing') {
                    $response = new Response(200, [
                        'Content-Type' => 'text/html; charset=utf-8',
                        'X-Guzzle-Redirect-History' => [
                            'https://example.com/1',
                            'https://example.com/2'
                        ],
                        'X-Guzzle-Redirect-Status-History' => [
                            "301",
                            "302"
                        ]
                    ]);
                } else {
                    $response = $client->get($url);
                }
                // var_dump($response->getBody()->getContents());
                $this->checkout_url = last($response->getHeader(\GuzzleHttp\RedirectMiddleware::HISTORY_HEADER));

                if (!$this->checkout_url) {
                    return $this->throw_checkout_page_not_loaded();
                } else {
                    if ($callback) {
                        $callback($this->checkout_url, $url);
                    } else {
                        if (!$unique_id) {
                            return $this->send();
                        }
                    }

                    return $this;
                }
            } catch (\Exception $e) {
                if (App::environment() == 'testing') { dd($e); }
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

    public function throw_checkout_page_not_loaded($e=null)
    {
        if($e) {
            throw $e;
        }
        if (\Lang::has('nano-to.checkout-page-not-loaded')) {
            throw new Error(__("nano-to.checkout-page-not-loaded"));
        } else {
            throw new Error("Unable to load Checkout Page.");
        }
    }
}
