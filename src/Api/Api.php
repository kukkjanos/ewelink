<?php

namespace EWeLink\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Sarahman\SimpleCache\FileSystemCache;

abstract class Api
{
    protected $config; // Config object

    public function __construct($config)
    {
        // Init variable
        $this->config = $config;

        // Cache library (file base implementation)
        $cache = new FileSystemCache(isset($this->config->settings['cachedir'])?$this->config->settings['cachedir']:'./cache'); // the custom cache directory can be set through the parameter.

        // Check cached key exists or not or expired.
        if ($cache->has('ewelink_token') == FALSE)
        {
            // Get new token
            if ($data = $this->auth()) {
                $cache->set('ewelink_token', $data, isset($this->config->settings['cachetime'])?$this->config->settings['cachetime']:3600);
            }
        }
        
        $this->cache = $cache->get('ewelink_token');

        return $config;
    }

    // Auth process get token
    public function auth()
    {
        $appDetails = [
            'password'  => $this->config->auth['password'],
            'version'   => '6',
            'ts'        => time(),
            'nonce'     => rand(10000, 99999).rand(10000, 99999).rand(10000, 99999),
            'appid'     => 'oeVkj2lYFGnJu5XUtWisfW4utiN4u9Mq',
            'imei'      => 'DF7425A0-'.rand(1000, 9999).'-'.rand(1000, 9999).'-9F5E-3BC9179E48FB',
            'os'        => 'iOS',
            'model'     => 'iPhone10,6',
            'romVersion'=> '11.1.2',
            'appVersion'=> '3.5.3'
        ];

        // Phone or email parameter needed
        if (isset($this->config->auth['phone']) && $this->config->auth['phone'] )
        {
            $appDetails['phoneNumber'] = $this->config->auth['phone'];
        }

        // Phone or email parameter needed
        if (isset($this->config->auth['email']) && $this->config->auth['email'] )
        {
            $appDetails['email'] = $this->config->auth['email'];
            unset($appDetails['phoneNumber']);
        }

        $jsonData = json_encode($appDetails);
        
        $hashMac = hash_hmac(
            'SHA256',
            $jsonData,
            '6Nz4n0xA8s8qdxQf2GqurZj2Fs55FUvM',
            true
        );
        
        $sign = base64_encode($hashMac);

        $client = new Client(['debug' => false]);

        $response = $client->request('POST', 'https://eu-api.coolkit.cc:8080/api/user/login', [
            'json' => $appDetails,
            'headers' => [
                'Authorization' => 'Sign ' . $sign,
            ]
        ]);

        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody(), true);
        }

        return false;
    }
}