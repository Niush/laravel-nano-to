<?php

namespace Niush\NanoTo\Tests\Feature;

use Illuminate\Support\Collection;
use Niush\NanoTo\NanoToApi;
use Niush\NanoTo\Tests\TestCase;

class NanoToApiTest extends TestCase
{
    protected $app;
    protected $use_real_api = false;

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
        $app['config']->set('nano-to.accounts', [
            'nano' => [
                'nano_3xxx',
            ],
        ]);
        $this->app = $app;
        if (env("USE_REAL_API")) {
            $this->use_real_api = true;
        }
    }

    /** @test */
    public function can_get_price_of_crypto_currency()
    {
        if (!$this->use_real_api) {
            $response = (object) [
                "symbol" => "NANO",
                "price" => 1.16,
                "currency" => "USD",
                "timestamp" => "June 10, 2022 6:36 AM",
                "timestamp_unix" => 1654842968
            ];
        } else {
            $response = NanoToApi::getPrice();
        }

        $this->assertObjectHasAttribute("symbol", $response);
        $this->assertObjectHasAttribute("price", $response);
        $this->assertObjectHasAttribute("currency", $response);
        $this->assertObjectHasAttribute("timestamp", $response);
        $this->assertEquals($response->currency, "USD");
        $this->assertEquals($response->symbol, "NANO");
    }

    /** @test */
    public function can_get_username_alias_info()
    {
        if (!$this->use_real_api) {
            $response = (object) [
                "id" => "0c873b370ee",
                "status" => "Active",
                "address" => "nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o",
                "namespace" => "moon",
                "expires" => "September 16, 2030 7:27 PM",
                "created" => "September 15, 2021 7:27 PM",
                "updated" => "September 17, 2021 8:39 PM",
            ];
        } else {
            $response = NanoToApi::getUsername("moon");
        }
        $this->assertObjectHasAttribute("status", $response);
        $this->assertEquals($response->namespace, "moon");
    }

    /** @test */
    public function can_get_nano_address_information()
    {
        if (!$this->use_real_api) {
            $response = (object) [
                "balance" => "3.726745204144926111560083887031",
                "frontier" => "A42CCCxxxx",
                "representative" => "nano_3chxxx",
                "height" => "100",
                "pending" => "0",
                "address" => "nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o",
                "usd_rate" => "5.22",
                "usd_value" => "19.45",
                "href" => "nano:nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o",
                "qrcode" => "data:image/png;base64,iVBORxxxx",
            ];
        } else {
            $response = NanoToApi::getNanoAddressInfo("nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o");
        }

        $this->assertObjectHasAttribute("balance", $response);
        $this->assertObjectHasAttribute("usd_value", $response);
        $this->assertObjectHasAttribute("pending", $response);
        if (isset($response->address)) {
            $this->assertEquals($response->address, "nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o");
        }
    }

    /** @test */
    public function can_get_total_nano_balance_of_all_address_combined()
    {
        if (!$this->use_real_api) {
            $response = (object) [
                "balance" => "3.726745204144926111560083887031",
                "pending" => "0",
                "balance_raw" => "3726745204144926111560083887031",
                "usd_value" => "19.45",
            ];
        } else {
            $this->app['config']->set('nano-to.accounts', [
                'nano' => [
                    'nano_378shkx4k3wd5gxmj3xnjwuxtaf9xrehyz7ugakpiemh8arxq8w9a9xniush',
                ],
            ]);
            $response = NanoToApi::getTotalNanoBalance();
        }

        $this->assertObjectHasAttribute("balance", $response);
        $this->assertObjectHasAttribute("usd_value", $response);
        $this->assertObjectHasAttribute("pending", $response);
    }

    /** @test */
    public function can_get_pending_nano_blocks()
    {
        if (!$this->use_real_api) {
            $response = collect([
                [
                    "type" => "receive",
                    "from" => "nano_19o64g3cy484nwfen76tfzz94icr1wn9bccw3ruefaham6x5hggpf6pz185x",
                    "amount" => "0.02112",
                    "hash" => "844FFE6D39D1F28673198E7C35A61C960148520FCBB8E2B2B0855C72D033FBF4",
                    "amount_raw" => "21120000000000000000000000000",
                    "timestamp" => null,
                ],
            ]);
        } else {
            $response = NanoToApi::getPendingNanoBlocks("nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o");
        }

        $this->assertTrue($response instanceof Collection);
        if (sizeof($response) > 0) {
            $this->assertArrayHasKey("amount", $response[0]);
            $this->assertArrayHasKey("hash", $response[0]);
            $this->assertArrayHasKey("from", $response[0]);
        }
    }

    /** @test */
    public function can_get_last_50_nano_address_history()
    {
        if (!$this->use_real_api) {
            $response = collect([
                [
                    "type" => "receive",
                    "account" => "nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o",
                    "amount" => "0.02143",
                    "height" => "244",
                    "hash" => "94E74C2EDAE153C181858BD28CFB67BA990EC8D1C43427658A118C947121A995",
                    "confirmed" => "true",
                    "amount_raw" => "21430000000000000000000000000",
                    "from" => "nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o",
                    "to" => "nano_3w64nbbzttyj8xqkibdc3wt4shczy8jtj365d76948hc45p1fmrbk1racy5a",
                ],
            ]);
        } else {
            $response = NanoToApi::getNanoAddressHistory("nano_378shkx4k3wd5gxmj3xnjwuxtaf9xrehyz7ugakpiemh8arxq8w9a9xniush");
        }

        $this->assertTrue($response instanceof Collection);
        if (sizeof($response) > 0) {
            $this->assertArrayHasKey("type", $response[0]);
            $this->assertArrayHasKey("account", $response[0]);
            $this->assertArrayHasKey("amount", $response[0]);
            $this->assertArrayHasKey("hash", $response[0]);
            $this->assertArrayHasKey("confirmed", $response[0]);
        }
    }

    /** @test */
    public function can_get_nano_transaction_by_amount()
    {
        if (!$this->use_real_api) {
            $response = (object) [
                "type" => "send",
                "account" => "nano_1xmastiputrdrhnf35jdh4yj1q339tyuk86w3k6oy5knede8zfowpa1rgjpn",
                "amount" => "0.0448222",
                "height" => "32",
                "hash" => "D26683BA57F53A8C6EC48152DD4396F8ED0579478D2E38F1B3BCC630734D1D08",
                "confirmed" => "true",
                "amount_raw" => "1000000000000000000000000000",
                "from" => "nano_1xmastiputrdrhnf35jdh4yj1q339tyuk86w3k6oy5knede8zfowpa1rgjpn",
                "to" => "nano_378shkx4k3wd5gxmj3xnjwuxtaf9xrehyz7ugakpiemh8arxq8w9a9xniush",
                "sender" => "@tree"
            ];
        } else {
            $response = NanoToApi::getNanoTransactionByAmount("nano_378shkx4k3wd5gxmj3xnjwuxtaf9xrehyz7ugakpiemh8arxq8w9a9xniush", "0.001");
        }

        $this->assertEquals("object", gettype($response));

        if ($response) {
            if (!isset($response->error)) {
                $this->assertObjectHasAttribute("from", $response);
                $this->assertObjectHasAttribute("hash", $response);
                $this->assertObjectHasAttribute("account", $response);
                $this->assertObjectHasAttribute("amount", $response);
            }
        }
    }

    /** @test */
    public function can_get_nano_transaction_by_hash()
    {
        if (!$this->use_real_api) {
            $response = (object) [
                "type" => "receive",
                "from" => "nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o",
                "to" => "nano_3tu8f7jou49pt9u448ck81fc7r7gd6gsdutheewoxqhaibxcceiqegoefx4h",
                "amount" => "0.02143",
                "height" => 1000,
            ];
        } else {
            $response = NanoToApi::getNanoTransactionByHash("94E74C2EDAE153C181858BD28CFB67BA990EC8D1C43427658A118C947121A995");
        }

        $this->assertEquals("object", gettype($response));

        if ($response) {
            if (!isset($response->error)) {
                $this->assertObjectHasAttribute("type", $response);
                $this->assertObjectHasAttribute("from", $response);
                $this->assertObjectHasAttribute("to", $response);
                $this->assertObjectHasAttribute("amount", $response);
                $this->assertObjectHasAttribute("height", $response);
            }
        }
    }

    /** @test */
    public function can_get_json_representation_of_checkout_url()
    {
        if (!$this->use_real_api) {
            $response = (object) [
                "price" => "1",
                "currency" => "USD",
                "color" => "black,white",
                "background" => "white,#1B9CFC",
                "id" => "8819d3e112c",
                "accept" => [
                    [
                        "symbol" => "nano",
                        "address" => "nano_3xxxx",
                    ],
                ],
                "expiration_unix" => 1654833600,
                "timestamp" => 1654844412794
            ];
        } else {
            $response = NanoToApi::getCheckoutUrlAsJson("https://nano.to/checkout/8819d3e112c");
        }

        $this->assertEquals("object", gettype($response));

        if ($response) {
            if (!isset($response->error)) {
                $this->assertObjectHasAttribute("price", $response);
                $this->assertObjectHasAttribute("currency", $response);
                $this->assertObjectHasAttribute("id", $response);
                $this->assertEquals("8819d3e112c", $response->id);
                $this->assertObjectHasAttribute("accept", $response);
                $this->assertObjectHasAttribute("expiration_unix", $response);
            }
        }
    }

    /** @test */
    public function can_get_list_of_public_representatives()
    {
        if (!$this->use_real_api) {
            $response = collect([
                [
                    "username" => "My Nano Ninja",
                    "rep_address" => "nano_1ninja7rh37ehfp9utkor5ixmxyg8kme8fnzc4zty145ibch8kf5jwpnzr3r",
                    "est_payment" => "0.000150",
                    "donation_address" => "nano_1ninja7rh37ehfp9utkor5ixmxyg8kme8fnzc4zty145ibch8kf5jwpnzr3r",
                    "weight" => 4.89,
                    "delegators" => 41774,
                    "uptime" => "good",
                    "synced" => 100,
                    "website" => "https://mynano.ninja",
                    "latitude" => 39.9458,
                    "longitude" => -74.9042,
                ],
            ]);
        } else {
            $response = NanoToApi::getListOfPublicRepresentatives("ninja");
        }

        $this->assertTrue($response instanceof Collection);
        if (sizeof($response) > 0) {
            $this->assertArrayHasKey("username", $response->first());
            $this->assertArrayHasKey("rep_address", $response->first());
            $this->assertArrayHasKey("weight", $response->first());
            $this->assertArrayHasKey("delegators", $response->first());

            $this->assertEquals("My Nano Ninja", $response->where("username", "My Nano Ninja")->first()['username']);
        }
    }

    /** @test */
    public function can_get_list_of_nano_usernames()
    {
        if (!$this->use_real_api) {
            $response = collect([
                [
                    "name" => "moon",
                    "address" => "nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o",
                    "expires" => "September 16, 2030",
                    "expires_unix" => 1915817220,
                ],
                [
                    "name" => "alberto",
                    "address" => "nano_3qr5jjnoqnk9fidfqxe7kewzawpx4yytrydkxonmso4jghzispgkszp8awp7",
                    "expires" => "October 16, 2031"
                ],
                [
                    "name" => "esteban",
                    "address" => "nano_1m747htgqw5fbhuafuswpwuc18y7zjwqntbi1fynehmz1zaqoj1puj7h96oj",
                    "expires" => "September 28, 2099"
                ]
            ]);
        } else {
            $response = NanoToApi::getListOfNanoUsernames("esteban");
        }

        $this->assertTrue($response instanceof Collection);
        if (sizeof($response) > 0) {
            $this->assertArrayHasKey("name", $response->first());
            $this->assertArrayHasKey("address", $response->first());
            $this->assertArrayHasKey("expires", $response->first());

            $this->assertEquals("esteban", $response->where("name", "esteban")->first()['name']);
        }
    }

    /** @test */
    public function nano_crawler_down_detector_should_work_as_expected()
    {
        if (!$this->use_real_api) {
            $isDown = false;
        } else {
            $isDown = NanoToApi::isNanoCrawlerDown();
        }

        $this->assertEquals("boolean", gettype($isDown));
    }

    /** @test */
    public function nano_to_down_detector_should_work_as_expected()
    {
        if (!$this->use_real_api) {
            $isDown = false;
        } else {
            $isDown = NanoToApi::isNanoToDown();
        }

        $this->assertEquals("boolean", gettype($isDown));
    }
}
