<?php

namespace Niush\NanoTo;

use GuzzleHttp\Client;

class NanoToApi
{
    /**
     * Get Nano.to API Base URL
     *
     * @return string
     */
    public static function getBaseUrl()
    {
        return config('nano-to.base_url', 'https://nano.to');
    }

    /**
     * Get CoinMarketCap conversion rate
     *
     * @param string $symbol
     * @param string $currency
     * @return object
     */
    public static function getPrice($symbol = "NANO", $currency = "USD")
    {
        $client = new Client();
        $response = $client->get(self::getBaseUrl() . '/price?symbol=' . $symbol . '&currency=' . $currency . '&json=true');
        return (object) json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get Nano.to Custom Username alias information
     *
     * @param string $username
     * @return object
     */
    public static function getUsername($username)
    {
        $client = new Client();
        $response = $client->get(self::getBaseUrl() . '/' . $username . '/username?json=true');
        return (object) json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get Nano Address Information
     *
     * @param string $address
     * @return object
     */
    public static function getNanoAddressInfo($address)
    {
        $client = new Client();
        $response = $client->get(self::getBaseUrl() . '/' . $address . '/account?json=true');
        return (object) json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get Total Nano Balance from all address provided in your config file
     *
     * @return object
     *
     * { balance: int, pending: int, balance_raw: int, usd_value: int }
     */
    public static function getTotalNanoBalance()
    {
        $accounts = config('nano-to.accounts.nano', []);
        $result = [
            "balance" => 0,
            "pending" => 0,
            "balance_raw" => 0,
            "usd_value" => 0,
        ];
        $client = new Client();
        foreach ($accounts as $account) {
            $response = json_decode($client->get(self::getBaseUrl() . '/' . $account . '/account?json=true')->getBody()->getContents(), true);
            $result['balance'] += $response["balance"] ?? 0;
            $result['pending'] += !empty($response["pending"]) ? $response["pending"] : 0;
            $result['balance_raw'] += $response["balance_raw"] ?? 0;
            $result['usd_value'] += $response["usd_value"] ?? 0;
        }
        return (object) $result;
    }

    /**
     * Get Pending Nano Blocks of given address
     *
     * @param string $address
     * @return \Illuminate\Support\Collection;
     */
    public static function getPendingNanoBlocks($address)
    {
        $client = new Client();
        $response = $client->get(self::getBaseUrl() . '/' . $address . '/pending?json=true');
        return collect(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * Get Last 50 Nano Block History of given address
     *
     * @param string $address
     * @return \Illuminate\Support\Collection
     */
    public static function getNanoAddressHistory($address)
    {
        $client = new Client();
        // $response = $client->get(self::getBaseUrl() . '/' . $address . '/history?json=true');
        $response = $client->get(self::getBaseUrl() . '/' . $address . '/history'); // Note: with ?json=true returns empty
        return collect(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * Get Nano Transaction by specific Amount. Either returns the block info OR object with { error: true }.
     * Note: Amount must be in Nano (MEGA) format.
     *
     * @param string $address
     * @param string $amount
     * @return object
     */
    public static function getNanoTransactionByAmount($address, $amount)
    {
        $client = new Client();
        $response = $client->get(self::getBaseUrl() . '/payment/' . $address . '/' . $amount . '?json=true');
        try {
            return (object) json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            return (object) ["error" => true];
        }
    }

    /**
     * Get Nano Transaction by block HASH. Either returns the block info OR object with { error: true }.
     *
     * @param string $hash
     * @return object
     */
    public static function getNanoTransactionByHash($hash)
    {
        $client = new Client();
        $response = $client->get(self::getBaseUrl() . '/hash/' . $hash . '?json=true');
        try {
            return (object) json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            return (object) ["error" => true];
        }
    }

    /**
     * Get JSON Representation of given checkout URL. Only has 12 hour lifespan.
     *
     * Either returns the Nano.to checkout info OR object with { error: true }.
     *
     * @param string $checkout_url
     * @return object
     */
    public static function getCheckoutUrlAsJson($checkout_url)
    {
        $client = new Client();
        $response = $client->get($checkout_url . '?json=true');
        try {
            return (object) json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            return (object) ["error" => "Checkout not found or expired."];
        }
    }

    /**
     * Get List Of Public Representatives for Nano
     *
     * @param string $search
     * @return \Illuminate\Support\Collection
     */
    public static function getListOfPublicRepresentatives($search = null)
    {
        $client = new Client();
        $response = $client->get(self::getBaseUrl() . '/reps?json=true');
        $reps = collect(json_decode($response->getBody()->getContents(), true)['representatives'] ?? []);

        if (!$search) {
            return $reps;
        } else {
            return $reps->filter(function ($rep) use ($search) {
                return (
                    stripos($rep['username'], $search) !== false
                    || stripos($rep['rep_address'], $search) !== false
                    || stripos($rep['website'], $search) !== false
                );
            });
        }
    }

    /**
     * Get List Of Nano.to known Usernames
     *
     * @param string $search
     * @return \Illuminate\Support\Collection
     */
    public static function getListOfNanoUsernames($search = null)
    {
        $client = new Client();
        $response = $client->get(self::getBaseUrl() . '/known?json=true');
        $users = collect(json_decode($response->getBody()->getContents(), true) ?? []);

        if (!$search) {
            return $users;
        } else {
            return $users->filter(function ($user) use ($search) {
                return (
                    stripos($user['name'], $search) !== false
                    || stripos($user['address'], $search) !== false
                    || stripos($user['expires'], $search) !== false
                );
            });
        }
    }

    /**
     * Check if https://nanocrawler.cc is down or unreachable.
     *
     * @return boolean
     */
    public static function isNanoCrawlerDown()
    {
        $client = new Client();
        try {
            $response = $client->get('https://api.nanocrawler.cc/version');
            $res = (object) json_decode($response->getBody()->getContents(), true);
            if ($res->network === "live") {
                return false;
            }
            return true;
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Check if Nano.to API is down or unreachable.
     *
     * @return boolean
     */
    public static function isNanoToDown()
    {
        $client = new Client();
        try {
            $response = $client->get(self::getBaseUrl() . '/status?json=true');
            $res = (object) json_decode($response->getBody()->getContents(), true);
            if ($res->status === true) {
                return false;
            }
            return true;
        } catch (Exception $e) {
            return true;
        }
    }
}
