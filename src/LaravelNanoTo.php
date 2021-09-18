<?php

namespace Niush\LaravelNanoTo;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

class LaravelNanoTo
{
    public $base_url = 'https://nano.to';
    public $title = "";
    public $description = "";
    public $amount;
    public $suggest;
    public $checkout_url;

    public function __construct()
    {
        $this->title = config('laravel-nano-to.title');
        $this->description = config('laravel-nano-to.description');
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
        $this->amount = $amount;
        return $this;
    }

    /**
     * Sets the suggested name and amount. Useful for quick donations.
     *
     * [ ["name" => "Coffee", "amount" => "10"], ["name" => "Meal", "amount" => "50"] ]
     *
     * @params array $suggest
     * @return Niush\LaravelNanoTo\LaravelNanoTo
     */
    public function suggest($suggest)
    {
        $parameters = array_map(function ($s) {
            return $s["name"] . ":" . $s["amount"];
        }, $suggest);

        $this->suggest = implode(",", $parameters);

        return $this;
    }

    /**
     * Redirect to the Nano.to Gateway URL
     *
     * @params integer|string $unique_id
     * @params function $callback
     * @return Illuminate\Http\RedirectResponse
     */
    public function create($unique_id = null, $callback = null)
    {
        $accounts = config('laravel-nano-to.accounts');
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
            '&webhook_secret=' . config('laravel-nano-to.webhook_secret');

            if ($this->amount) {
                $url .= '&price=' . $this->amount;
            } elseif ($this->suggest) {
                $url .= '&suggest=' . $this->suggest;
            }

            try {
                $client = new Client(['allow_redirects' => ['track_redirects' => true]]);
                $response = $client->get($url);
                // var_dump($response->getBody()->getContents());
                $this->checkout_url = last($response->getHeader(\GuzzleHttp\RedirectMiddleware::HISTORY_HEADER));

                if (!$this->checkout_url) {
                    return $this->throw_checkout_page_not_loaded();
                } else {
                    if ($callback) {
                        $callback($this->checkout_url);
                    } else {
                        if (!$unique_id) {
                            return $this->send();
                        }
                    }

                    return $this;
                }
            } catch (\Exception $e) {
                return $this->throw_checkout_page_not_loaded();
            }
        } else {
            if (\Lang::has('nano-to.no-receiver')) {
                throw new Error(__("nano-to.no-receiver"));
            } else {
                throw new Error("Receiver Account was not available.");
            }
        }
    }

    public function send()
    {
        return redirect()->to($this->checkout_url);
    }

    public function throw_checkout_page_not_loaded()
    {
        if (\Lang::has('nano-to.checkout-page-not-loaded')) {
            throw new Error(__("nano-to.checkout-page-not-loaded"));
        } else {
            throw new Error("Unable to load Checkout Page.");
        }
    }
}
