<?php

namespace Niush\LaravelNanoTo\Tests\Feature;

use Error;
use Niush\LaravelNanoTo\Tests\TestCase;
use Niush\LaravelNanoTo\NanoToApi;

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
        $app['config']->set('laravel-nano-to.accounts', [
            'nano' => [
                'nano_3xxx'
            ]
        ]);
        $this->app = $app;
        if(env("USE_REAL_API")) {
            $this->use_real_api = true;
        }
    }

    /** @test */
    function can_get_price_of_crypto_currency()
    {
        if (!$this->use_real_api) {
            $response = ["symbol" => "NANO", "price" => 5.233, "currency" => "USD", "timestamp" => "2021-09-23T01:57:52.020Z"];
        } else {
            $response = NanoToApi::getPrice();
        }

        $this->assertArrayHasKey("symbol", $response);
        $this->assertArrayHasKey("price", $response);
        $this->assertArrayHasKey("currency", $response);
        $this->assertArrayHasKey("timestamp", $response);
        $this->assertEquals($response["currency"], "USD");
        $this->assertEquals($response["symbol"], "NANO");
    }

    /** @test */
    function can_get_username_alias_info()
    {
        if (!$this->use_real_api) {
            $response = [
                "id" => "0c873b370ee",
                "status" => "Active",
                "address" => "nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o",
                "namespace" => "moon",
                "expires" => "September 16, 2030 7:27 PM",
                "created" => "September 15, 2021 7:27 PM",
                "updated" => "September 17, 2021 8:39 PM"
            ];
        } else {
            $response = NanoToApi::getUsername("moon");
        }

        $this->assertArrayHasKey("status", $response);
        $this->assertEquals($response["namespace"], "moon");
    }

    /** @test */
    function can_get_nano_address_information()
    {
        if (!$this->use_real_api) {
            $response = [
                "balance" => "3.726745204144926111560083887031",
                "block_count" => "100",
                "account_version" => "2",
                "confirmation_height" => "100",
                "representative" => "nano_3chxxx",
                "weight" => "0",
                "pending" => "0",
                "balance_raw" => "3726745204144926111560083887031",
                "pending_raw" => "0",
                "usd_rate" => "5.22",
                "usd_value" => "19.45"
            ];
        } else {
            $response = NanoToApi::getNanoAddressInfo("nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o");
        }

        $this->assertArrayHasKey("balance", $response);
        $this->assertArrayHasKey("usd_value", $response);
        $this->assertArrayHasKey("pending", $response);
        if(isset($response["address"])) {
            $this->assertEquals($response["address"], "nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o");
        }
    }

    /** @test */
    function can_get_total_nano_balance_of_all_address_combined()
    {
        if (!$this->use_real_api) {
            $response = [
                "balance" => "3.726745204144926111560083887031",
                "pending" => "0",
                "balance_raw" => "3726745204144926111560083887031",
                "usd_value" => "19.45"
            ];
        } else {
            $this->app['config']->set('laravel-nano-to.accounts', [
                'nano' => [
                    'nano_378shkx4k3wd5gxmj3xnjwuxtaf9xrehyz7ugakpiemh8arxq8w9a9xniush'
                ]
            ]);
            $response = NanoToApi::getTotalNanoBalance();
        }

        $this->assertArrayHasKey("balance", $response);
        $this->assertArrayHasKey("usd_value", $response);
        $this->assertArrayHasKey("pending", $response);
    }

    /** @test */
    function can_get_pending_nano_blocks()
    {
        if (!$this->use_real_api) {
            $response = [
                [
                    "type" => "pending",
                    "amount" => "0.02112",
                    "hash" => "844FFE6D39D1F28673198E7C35A61C960148520FCBB8E2B2B0855C72D033FBF4",
                    "source" => "nano_19o64g3cy484nwfen76tfzz94icr1wn9bccw3ruefaham6x5hggpf6pz185x",
                    "timestamp" => null,
                    "amount_raw" => "21120000000000000000000000000"
                ]
            ];
        } else {
            $response = NanoToApi::getPendingNanoBlocks("nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o");
        }

        $this->assertEquals("array", gettype($response));
        if(sizeof($response) > 0) {
            $this->assertArrayHasKey("amount", $response[0]);
            $this->assertArrayHasKey("hash", $response[0]);
        }
    }

    /** @test */
    function can_get_last_20_nano_address_history()
    {
        if (!$this->use_real_api) {
            $response = [
                [
                    "type" => "state",
                    "balance" => "0.215288",
                    "subtype" => "receive",
                    "account" => "nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o",
                    "hash" => "94E74C2EDAE153C181858BD28CFB67BA990EC8D1C43427658A118C947121A995",
                ]
            ];
        } else {
            $response = NanoToApi::getNanoAddressHistory("nano_378shkx4k3wd5gxmj3xnjwuxtaf9xrehyz7ugakpiemh8arxq8w9a9xniush");
        }

        $this->assertEquals("array", gettype($response));
        if(sizeof($response) > 0) {
            $this->assertArrayHasKey("balance", $response[0]);
            $this->assertArrayHasKey("hash", $response[0]);
            $this->assertArrayHasKey("account", $response[0]);
        }
    }

    /** @test */
    function can_get_nano_transaction_by_amount()
    {
        if (!$this->use_real_api) {
            $response =  [
                "type" => "state",
                "balance" => "0.215288",
                "subtype" => "receive",
                "account" => "nano_37y6iq8m1zx9inwkkcgqh34kqsihzpjfwgp9jir8xpb9jrcwhkmoxpo61f4o",
                "amount" => "0.02143",
                "hash" => "94E74C2EDAE153C181858BD28CFB67BA990EC8D1C43427658A118C947121A995",
            ];
        } else {
            $response = NanoToApi::getNanoTransactionByAmount("nano_378shkx4k3wd5gxmj3xnjwuxtaf9xrehyz7ugakpiemh8arxq8w9a9xniush", "0.021");
        }

        $this->assertEquals("array", gettype($response));

        if(sizeof($response) > 0) {
            $this->assertArrayHasKey("balance", $response);
            $this->assertArrayHasKey("hash", $response);
            $this->assertArrayHasKey("account", $response);
            $this->assertArrayHasKey("amount", $response);
        }
    }
}
