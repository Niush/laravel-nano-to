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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Niush\LaravelNanoTo\LaravelNanoToFacade as LaravelNanoTo;

class LaravelNanoToTest extends TestCase
{
    protected $app;
    protected $testing_checkout_url = "https://example.com/2";

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
        $response = LaravelNanoTo::amount(9.99)->create('unique_id', function($checkout_url, $original_url) {
            $this->assertEquals($this->testing_checkout_url, $checkout_url);
        })->send();

        $this->assertEquals(302, $response->status());
        $this->assertEquals($this->testing_checkout_url, $response->headers->get('location'));
    }

    /** @test */
    function info_and_suggest_function_works()
    {
        $nano_to_url = "https://nano.to/nano_3xxx".
            "?title=Payment for Subscription".
            "&description=<i>Also accepts HTML</i>".
            "&success_url=".route(config('laravel-nano-to.success_url'), 'unique_id').
            "&cancel_url=".route(config('laravel-nano-to.cancel_url'), 'unique_id').
            "&webhook_url=".route(config('laravel-nano-to.webhook_url'), 'unique_id').
            "&webhook_secret=".
            "&suggest=Coffee:10,Meal:50";

        $response = LaravelNanoTo::info("Payment for Subscription", "<i>Also accepts HTML</i>")
        ->suggest([
            ["name" => "Coffee", "amount" => "10"],
            ["name" => "Meal", "amount" => "50"]
        ])
        ->create('unique_id', function($checkout_url, $original_url) use ($nano_to_url) {
            $this->assertEquals($nano_to_url, $original_url);
        });
    }

    /** @test */
    function webhook_secret_env_appends_if_exists()
    {
        $this->app['config']->set('laravel-nano-to.webhook_secret', '123456');
        $nano_to_url = "https://nano.to/nano_3xxx".
            "?title=".config('laravel-nano-to.title').
            "&description=".config('laravel-nano-to.description').
            "&success_url=".route(config('laravel-nano-to.success_url'), 'unique_id').
            "&cancel_url=".route(config('laravel-nano-to.cancel_url'), 'unique_id').
            "&webhook_url=".route(config('laravel-nano-to.webhook_url'), 'unique_id').
            "&webhook_secret=123456";

        $response = LaravelNanoTo::create('unique_id', function($checkout_url, $original_url) use ($nano_to_url) {
            $this->assertEquals($nano_to_url, $original_url);
        });
    }

    /** @test */
    function can_use_custom_webook_secret()
    {
        $this->app['config']->set('laravel-nano-to.webhook_secret', '123456');
        $nano_to_url = "https://nano.to/nano_3xxx".
            "?title=".config('laravel-nano-to.title').
            "&description=".config('laravel-nano-to.description').
            "&success_url=".route(config('laravel-nano-to.success_url'), 'unique_id').
            "&cancel_url=".route(config('laravel-nano-to.cancel_url'), 'unique_id').
            "&webhook_url=".route(config('laravel-nano-to.webhook_url'), 'unique_id').
            "&webhook_secret=123456-custom";

        $response = LaravelNanoTo::secret(
            config("laravel-nano-to.webhook_secret") . "-custom"
        )->create('unique_id', function($checkout_url, $original_url) use ($nano_to_url) {
            $this->assertEquals($nano_to_url, $original_url);
        });
    }
}
