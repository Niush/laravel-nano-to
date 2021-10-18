<?php

namespace Niush\LaravelNanoTo;

use Error;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\App;

class NanoToApi
{
    private static $api_base_url = 'https://api.nano.to';

    /**
     * Get CoinMarketCap conversion rate
     */
    public static function getPrice($symbol="NANO", $currency="USD")
    {
        $client = new Client();
        $response = $client->get(self::$api_base_url . '/price?symbol=' . $symbol . '&currency=' . $currency);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get Nano.to Custom Username alias information
     */
    public static function getUsername($username)
    {
        $client = new Client();
        $response = $client->get(self::$api_base_url . '/name/' . $username);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get Nano Address Information
     */
    public static function getNanoAddressInfo($address)
    {
        $client = new Client();
        $response = $client->get(self::$api_base_url . '/account/' . $address);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get Total Nano Balance from all address provided in config file
     */
    public static function getTotalNanoBalance()
    {
        $accounts = config('laravel-nano-to.accounts.nano', []);
        $result = [
            "balance" => 0,
            "pending" => 0,
            "balance_raw" => 0,
            "usd_value" => 0,
        ];
        $client = new Client();
        foreach($accounts as $account) {
            $response = json_decode($client->get(self::$api_base_url . '/account/' . $account)->getBody()->getContents(), true);
            $result['balance'] += $response["balance"] ?? 0;
            $result['pending'] += $response["pending"] ?? 0;
            $result['balance_raw'] += $response["balance_raw"] ?? 0;
            $result['usd_value'] += $response["usd_value"] ?? 0;
        }
        return $result;
    }

    /**
     * Get Pending Nano Blocks
     */
    public static function getPendingNanoBlocks($address)
    {
        $client = new Client();
        $response = $client->get(self::$api_base_url . '/pending/' . $address);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get Last 20 Nano Address History
     */
    public static function getNanoAddressHistory($address)
    {
        $client = new Client();
        $response = $client->get(self::$api_base_url . '/history/' . $address);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get Nano Transaction by specific Amount
     * Amount must be in Nano (MEGA) format.
     */
    public static function getNanoTransactionByAmount($address, $amount)
    {
        $client = new Client();
        $response = $client->get(self::$api_base_url . '/payment/' . $address . '/' . $amount);
        return json_decode($response->getBody()->getContents(), true);
    }
}
