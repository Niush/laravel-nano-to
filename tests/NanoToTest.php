<?php

namespace Niush\NanoTo\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Niush\NanoTo\NanoToFacade as NanoTo;
use Niush\NanoTo\Tests\TestCase;

class NanoToTest extends TestCase
{
    protected $app;
    protected $testing_checkout_url = "https://example.com/test_id";
    protected $use_real_api = false;

    /**
     * Set up the test
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!$this->use_real_api) {
            $mock = new MockHandler(collect([1, 2])->map(fn() => $this->buildMockResponse())->toArray());

            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);

            $this->app->instance('GuzzleHttp\Client', $client);
        }
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
        $app['config']->set('nano-to.accounts', [
            'nano' => [
                'nano_378shkx4k3wd5gxmj3xnjwuxtaf9xrehyz7ugakpiemh8arxq8w9a9xniush',
            ],
        ]);
        $this->app = $app;
        if (env("USE_REAL_API")) {
            // $this->use_real_api = true;
        }
    }

    public function buildMockResponse()
    {
        $response_json = '{"id":"test_id", "url":"' . $this->testing_checkout_url . '", "exp":"2021-10-10T01:51:23.853Z"}';
        return new Response(
            200, ['Content-Type' => 'application/json; charset=utf-8'],
            $response_json
        );
    }

    /** @test */
    public function fails_if_accounts_config_is_empty()
    {
        $this->app['config']->set('nano-to.accounts', [
            'nano' => [],
        ]);
        try {
            $response = NanoTo::amount(9.99)->create('unique_id')->send();
        } catch (\Error$e) {
            $this->assertStringContainsString($e->getMessage(), 'Receiver Account was not available.');
        }
    }

    /** @test */
    public function redirects_to_checkout_and_create_functions_callback_is_also_equal()
    {
        $response = NanoTo::amount(9.99)->create('unique_id', function ($checkout_url) {
            if ($this->use_real_api) {
                $this->assertStringStartsWith($this->app['config']->get('nano-to.base_url'), $checkout_url);
            } else {
                $this->assertEquals($this->testing_checkout_url, $checkout_url);
            }
        })->send();

        $this->assertEquals(302, $response->status());
        if ($this->use_real_api) {
            $this->assertStringStartsWith($this->app['config']->get('nano-to.base_url'), $response->headers->get('location'));
        } else {
            $this->assertEquals($this->testing_checkout_url, $response->headers->get('location'));
        }
    }

    /** @test */
    public function info_and_suggest_function_works()
    {
        $expected_body = [
            "title" => "Payment for Subscription",
            "description" => "<i>Also accepts HTML</i>",
            "success_url" => route(config('nano-to.success_url'), 'unique_id'),
            "cancel_url" => route(config('nano-to.cancel_url'), 'unique_id'),
            "webhook_url" => route(config('nano-to.webhook_url'), 'unique_id'),
            "webhook_secret" => "",
            "background" => config('nano-to.background'),
            "color" => config('nano-to.color'),
            "raw" => false,
            "business" => [
                "name" => config('nano-to.business.name'),
                "logo" => config('nano-to.business.logo'),
                "favicon" => config('nano-to.business.favicon'),
            ],
            "plans" => [
                ["name" => "Coffee", "price" => "10"],
                ["name" => "Meal", "price" => "50"],
            ],
        ];

        $response = NanoTo::info("Payment for Subscription", "<i>Also accepts HTML</i>")
            ->suggest([
                ["name" => "Coffee", "price" => "10"],
                ["name" => "Meal", "price" => "50"],
            ])
            ->create('unique_id', function ($checkout_url, $body) use ($expected_body) {
                $this->assertEquals($expected_body, $body);
            });
    }

    /** @test */
    public function webhook_secret_env_appends_if_exists()
    {
        $expected_body = [
            "title" => config('nano-to.title'),
            "description" => config('nano-to.description'),
            "success_url" => route(config('nano-to.success_url'), 'unique_id'),
            "cancel_url" => route(config('nano-to.cancel_url'), 'unique_id'),
            "webhook_url" => route(config('nano-to.webhook_url'), 'unique_id'),
            "webhook_secret" => "123456",
            "background" => config('nano-to.background'),
            "color" => config('nano-to.color'),
            "raw" => false,
            "business" => [
                "name" => config('nano-to.business.name'),
                "logo" => config('nano-to.business.logo'),
                "favicon" => config('nano-to.business.favicon'),
            ],
        ];

        $this->app['config']->set('nano-to.webhook_secret', '123456');

        $response = NanoTo::create('unique_id', function ($checkout_url, $body) use ($expected_body) {
            $this->assertEquals($expected_body, $body);
        });
    }

    /** @test */
    public function can_use_custom_webhook_secret()
    {
        $this->app['config']->set('nano-to.webhook_secret', '123456');

        $response = NanoTo::secret(
            config("nano-to.webhook_secret") . "-custom"
        )->create('unique_id', function ($checkout_url, $body) {
            $this->assertEquals($body["webhook_secret"], '123456-custom');
        });
    }

    /** @test */
    public function can_apply_business_customization()
    {
        $business = [
            "name" => "My Company",
            "logo" => "https://example.com/logo.png",
            "favicon" => "https://example.com/logo.ico",
        ];
        $this->app['config']->set('nano-to.business', $business);

        $response = NanoTo::create('unique_id', function ($checkout_url, $body) use ($business) {
            $this->assertEquals($body["business"], $business);
        });

        $response = NanoTo::business([
            "name" => "My Custom Company",
        ])->create('unique_id', function ($checkout_url, $body) use ($business) {
            $this->assertEquals($body["business"]["name"], "My Custom Company");
        });
    }

    /** @test */
    public function can_apply_background_and_color_customization()
    {
        $background = "#000000,#FFFFFF";
        $color = "#FFFFFF,#000000";

        $this->app['config']->set('nano-to.background', $background);
        $this->app['config']->set('nano-to.color', $color);

        $response = NanoTo::create('unique_id', function ($checkout_url, $body) use ($background, $color) {
            $this->assertEquals($body["background"], $background);
            $this->assertEquals($body["color"], $color);
        });

        $background = "#111111,#EEEEEE";
        $color = "#EEEEEE,#111111";

        $response = NanoTo::background($background)->color($color)
            ->create('unique_id', function ($checkout_url, $body) use ($background, $color) {
                $this->assertEquals($body["background"], $background);
                $this->assertEquals($body["color"], $color);
            });
    }

    /** @test */
    public function metadata_can_be_added()
    {
        $metadata = [
            "payment_type" => "monthly",
        ];

        $response = NanoTo::metadata($metadata)->create('unique_id', function ($checkout_url, $body) use ($metadata) {
            $this->assertEquals($body["metadata"], $metadata);
        });
    }

    /** @test */
    public function can_ask_to_use_raw_amount_in_qr()
    {
        $response = NanoTo::asRaw()->amount(9.99)->create("unique_id", function ($checkout_url, $body) {
            $this->assertEquals($body["raw"], true);
        })->send();
    }

    /** @test */
    public function can_add_custom_image_to_checkout_url()
    {
        $image_url = "https://dummyimage.com/300";
        $response = NanoTo::withImage($image_url)->amount(9.99)->create("unique_id", function ($checkout_url, $body) use ($image_url) {
            $this->assertEquals($body["image"], $image_url);
        })->send();
    }
}
