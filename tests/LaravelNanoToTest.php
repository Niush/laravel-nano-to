<?php

namespace Niush\LaravelNanoTo\Tests\Feature;

use Error;
use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use Niush\LaravelNanoTo\Tests\TestCase;
use GuzzleHttp\Exception\RequestException;
use Niush\LaravelNanoTo\LaravelNanoToFacade as LaravelNanoTo;

class LaravelNanoToTest extends TestCase
{
    protected $app;
    protected $testing_checkout_url = "https://example.com/1";

    /**
     * Set up the test
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app['config']->set('app.env', 'local');
        $app['config']->set('app.debug', true);
        $app['config']->set('laravel-nano-to.accounts', [
            'nano' => [
                'nano_3xxx'
            ]
        ]);
        $this->app = $app;
    }

    /** @test */
    function fails_if_accounts_config_is_empty()
    {
        $this->app['config']->set('laravel-nano-to.accounts', [
            'nano' => []
        ]);
        try {
            $response = LaravelNanoTo::amount(9.99)->create('unique_id')->send();
        }
        catch (\Error $e) {
            $this->assertStringContainsString($e->getMessage(), 'Receiver Account was not available.');
        }
    }

    /** @test */
    function redirects_to_checkout_and_create_functions_callback_is_also_equal()
    {
        $response = LaravelNanoTo::amount(9.99)->create('unique_id', function($checkout_url) {
            $this->assertEquals($this->testing_checkout_url, $checkout_url);
        })->send();

        $this->assertEquals(302, $response->status());
        $this->assertEquals($this->testing_checkout_url, $response->headers->get('location'));
    }

    /** @test */
    function info_and_suggest_function_works()
    {
        $expected_body = [
            "title" => "Payment for Subscription",
            "description" => "<i>Also accepts HTML</i>",
            "success_url" => route(config('laravel-nano-to.success_url'), 'unique_id'),
            "cancel_url" => route(config('laravel-nano-to.cancel_url'), 'unique_id'),
            "webhook_url" => route(config('laravel-nano-to.webhook_url'), 'unique_id'),
            "webhook_secret" => "",
            "background" => config('laravel-nano-to.background'),
            "color" => config('laravel-nano-to.color'),
            "raw" => false,
            "business" => [
                "name" => config('laravel-nano-to.business.name'),
                "logo" => config('laravel-nano-to.business.logo'),
                "favicon" => config('laravel-nano-to.business.favicon'),
            ],
            "plans" => [
                ["name" => "Coffee", "price" => "10"],
                ["name" => "Meal", "price" => "50"]
            ]
        ];

        $response = LaravelNanoTo::info("Payment for Subscription", "<i>Also accepts HTML</i>")
        ->suggest([
            ["name" => "Coffee", "price" => "10"],
            ["name" => "Meal", "price" => "50"]
        ])
        ->create('unique_id', function($checkout_url, $body) use ($expected_body) {
            $this->assertEquals($expected_body, $body);
        });
    }

    /** @test */
    function webhook_secret_env_appends_if_exists()
    {
        $expected_body = [
            "title" => config('laravel-nano-to.title'),
            "description" => config('laravel-nano-to.description'),
            "success_url" => route(config('laravel-nano-to.success_url'), 'unique_id'),
            "cancel_url" => route(config('laravel-nano-to.cancel_url'), 'unique_id'),
            "webhook_url" => route(config('laravel-nano-to.webhook_url'), 'unique_id'),
            "webhook_secret" => "123456",
            "background" => config('laravel-nano-to.background'),
            "color" => config('laravel-nano-to.color'),
            "raw" => false,
            "business" => [
                "name" => config('laravel-nano-to.business.name'),
                "logo" => config('laravel-nano-to.business.logo'),
                "favicon" => config('laravel-nano-to.business.favicon'),
            ]
        ];

        $this->app['config']->set('laravel-nano-to.webhook_secret', '123456');

        $response = LaravelNanoTo::create('unique_id', function($checkout_url, $body) use ($expected_body) {
            $this->assertEquals($expected_body, $body);
        });
    }

    /** @test */
    function can_use_custom_webhook_secret()
    {
        $this->app['config']->set('laravel-nano-to.webhook_secret', '123456');

        $response = LaravelNanoTo::secret(
            config("laravel-nano-to.webhook_secret") . "-custom"
        )->create('unique_id', function($checkout_url, $body) {
            $this->assertEquals($body["webhook_secret"], '123456-custom');
        });
    }

    /** @test */
    function can_apply_business_customization()
    {
        $business = [
            "name" => "My Company",
            "logo" => "https://example.com/logo.png",
            "favicon" => "https://example.com/logo.ico"
        ];
        $this->app['config']->set('laravel-nano-to.business', $business);

        $response = LaravelNanoTo::create('unique_id', function($checkout_url, $body) use ($business) {
            $this->assertEquals($body["business"], $business);
        });

        $response = LaravelNanoTo::business([
            "name" => "My Custom Company"
        ])->create('unique_id', function($checkout_url, $body) use ($business) {
            $this->assertEquals($body["business"]["name"], "My Custom Company");
        });
    }

    /** @test */
    function metadata_can_be_added()
    {
        $metadata = [
            "payment_type" => "monthly"
        ];

        $response = LaravelNanoTo::metadata($metadata)->create('unique_id', function($checkout_url, $body) use ($metadata) {
            $this->assertEquals($body["metadata"], $metadata);
        });
    }

    /** @test */
    function can_ask_to_use_raw_amount_in_qr()
    {
        $response = LaravelNanoTo::asRaw()->amount(9.99)->create("unique_id", function($checkout_url, $body) {
            $this->assertEquals($body["raw"], true);
        })->send();
    }

    /** @test */
    function deprecated_get_function_is_still_working_as_expected()
    {
        $nano_to_url = "https://nano.to/nano_3xxx".
            "?title=Payment for Subscription".
            "&description=<i>Also accepts HTML</i>".
            "&success_url=".route(config('laravel-nano-to.success_url'), 'unique_id').
            "&cancel_url=".route(config('laravel-nano-to.cancel_url'), 'unique_id').
            "&webhook_url=".route(config('laravel-nano-to.webhook_url'), 'unique_id').
            "&webhook_secret=".
            "&raw=false".
            "&suggest=Coffee:10,Meal:50";

        $response = LaravelNanoTo::info("Payment for Subscription", "<i>Also accepts HTML</i>")
        ->suggest([
            ["name" => "Coffee", "price" => "10"],
            ["name" => "Meal", "price" => "50"]
        ])
        ->createWithGetRequest('unique_id', function($checkout_url, $original_url) use ($nano_to_url) {
            $this->assertEquals($nano_to_url, $original_url);
        });
    }
}
