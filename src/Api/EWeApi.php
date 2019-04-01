<?php

namespace EWeLink\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class EWeApi extends Api
{
    public function __construct($config)
    {
        parent::__construct($config);
    }

    public function getDevices()
    {
        $client = new Client(['debug' => false]);

        $response = $client->request('GET', 'https://'. $this->cache['region'] .'-api.coolkit.cc:8080/api/user/device?lang=en&apiKey='. $this->cache['user']['apikey'] .'&getTags=1', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->cache['at'],
            ]
        ]);

        return json_decode($response->getBody(), true);
    }
}
