<?php

namespace EWeLink\Api;

class EWeApi extends Api
{
    public function __construct($config)
    {
        parent::__construct($config);

        // Guzzle AS
        $this->EWeAPI = $this->GuzzleClient;
    }

    public function getDevices()
    {
        $response = $this->EWeAPI->request('GET', 'https://'. $this->cache['region'] .'-api.coolkit.cc:8080/api/user/device?lang=en&apiKey='. $this->cache['user']['apikey'] .'&getTags=1', ['headers' => $this->getAuthHeader()]);

        return json_decode($response->getBody(), true);
    }

    public function getDevice($deviceId)
    {
        $response = $this->EWeAPI->request('GET', 'https://'. $this->cache['region'] .'-api.coolkit.cc:8080/api/user/device/'. $deviceId .'?lang=en&apiKey='. $this->cache['user']['apikey'] .'&getTags=1', ['headers' => $this->getAuthHeader()]);

        return json_decode($response->getBody(), true);
    }

    public function toggleDevice($deviceId, $outlet = 0)
    {
        // Get Device
        $data = $this->getDevice($deviceId);

        $params = array();

        // Single switch support
        if (isset($data['params']['switch']))
        {
            $params = ['switch' => $data['params']['switch'] == 'on'?'off':'on'];
        }

        // Multi switch support
        if (isset($data['params']['switches']))
        {
            // Count
            $switchCount = count($data['params']['switches']);

            // Upload array
            for ($o = 0; $o < $switchCount; $o++)
            {
                $element[] = [
                    'switch' => $o == $outlet?$data['params']['switches'][$o]['switch']=='on'?'off':'on':$data['params']['switches'][$o]['switch'],
                    'outlet' => $o
                ];
            }

            $params = ['switches' =>  $element];
        }

        $payLoad = array(
            'action'      => 'update',
            'userAgent'   => 'app',
            'params'      => $params,
            'apikey'      => $this->cache['user']['apikey'],
            'deviceid'    => $deviceId,
            'sequence'    => time()
        );

        if ($data['apikey'] != $this->cache['user']['apikey']) {
            $payLoad['selfApikey'] = $data['apikey'];
        }

        return $this->sendWSMessage($payLoad);
    }

}
