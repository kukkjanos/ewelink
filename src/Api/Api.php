<?php

namespace EWeLink\Api;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

use WebSocket\Client as WebSocketClient;
use Sarahman\SimpleCache\FileSystemCache;

abstract class Api
{
    // Config object
    protected $config;

    public function __construct($config)
    {
        // Init variable and/or Build cache
        $this->config = $config;

        // Init Guzzle
        $this->GuzzleClient = new GuzzleClient();

        // Build cache (need Guzzle)
        $this->cache = $this->cacheBuild();

        // Init WebSocket (need Cache)
        $this->WebSocketClient = new WebSocketClient('wss://'. $this->getWebSocketDomain() .':8080/api/ws');

        return $config;
    }

    // Cache build
    private function cacheBuild($force = FALSE)
    {
        // Cache library (file base implementation)
        $cache = new FileSystemCache($this->getCachedir()); // the custom cache directory can be set through the parameter.

        // Check cached key exists or not or expired.
        if ($cache->has('ewelink_token') == $force)
        {
            // Get new token
            if ($data = $this->authProc()) {
                $cache->set('ewelink_token', $data, isset($this->config->settings['cachetime'])?$this->config->settings['cachetime']:3600);
            }
        }
        
        // Build
        return $cache->get('ewelink_token');
    }

    // Auth process get token
    private function authProc()
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

        $response = $this->GuzzleClient->request('POST', 'https://'. $this->getRegion() .'-api.coolkit.cc:8080/api/user/login', [
            'json' => $appDetails,
            'headers' => [
                'Authorization' => 'Sign ' . $sign,
            ]
        ]);

        if ($response->getStatusCode() == 200)
        {
            $bodyResponse = json_decode($response->getBody(), true);

            if (isset($bodyResponse['error']))
            {
                switch ($bodyResponse['error'])
                {
                    case 301:
                        throw new \Exception('Regio error! Please change region: '. $bodyResponse['region']);
                        break;

                    case 401:
                        throw new \Exception('Auth error');
                        break;

                    default:
                        throw new \Exception('Fatal error: '. $bodyResponse['error']);
                        break;
                }
            } else {
                return json_decode($response->getBody(), true);
            }
        }

        return false;
    }

    public function sendWSMessage($payload = array())
    {
        $initPayload = array(
            'action'     => 'userOnline',
            'userAgent'  => 'app',
            'version'    => 6,
            'nonce'      => rand(10000, 99999).rand(10000, 99999).rand(10000, 99999),
            'apkVesrion' => "1.8",
            'os'         => 'ios',
            'at'         => $this->cache['at'],
            'apikey'     => $this->cache['user']['apikey'],
            'ts'         => time(),
            'model'      => 'iPhone10,6',
            'romVersion' => '11.1.2',
            'sequence'   => time()
        );

        $initPayloadString = json_encode($initPayload);

        $this->WebSocketClient->send($initPayloadString);
        $resp = json_decode($this->WebSocketClient->receive(), true);

        if (!empty($payload))
        {
            $payloadString = json_encode($payload);

            $this->WebSocketClient->send($payloadString);
            $resp = json_decode($this->WebSocketClient->receive(), true);
        }

        if ($resp['error']) {
            return ['error' => $resp['error']];
        } else {
            return ['params' => $payload['params']];
        }
    }

    public function getAuthHeader()
    {
        return ['Authorization' => 'Bearer ' . $this->cache['at']];
    }

    private function getWebSocketDomain()
    {
        $response = $this->GuzzleClient->request('POST', 'https://'. $this->getRegion() .'-disp.coolkit.cc:8080/dispatch/app', ['headers' => $this->getAuthHeader()]);

        $domain = json_decode($response->getBody(), true);

        if (isset($domain['error']) && $domain['error'] == 0) {
            return $domain['domain'];
        } else {
            throw new Exception('Domain dispatch error');
        }
    }

    private function getRegion()
    {
        return isset($this->cache['region'])?$this->cache['region']:$this->config->auth['region'];
    }

    private function getCachedir()
    {
        return isset($this->config->settings['cachedir'])?$this->config->settings['cachedir']:'./cache';
    }

}