<?php

namespace EWeLink\Api;

class Config
{
    protected $configParams;

    public function __construct($configParams)
    {
        // Valid eWeLink parameters (required)
        $config = [
            // Auth parameters
            'auth' => [
                'email'    => @$configParams['auth']['email'],
                'password' => @$configParams['auth']['password'],
                'region'   => @$configParams['auth']['region']
            ]
        ];

        // Require parameters Exceptions
        if ((FALSE === isset($configParams['auth']['email'])) && (FALSE === isset($configParams['auth']['phone'])) )
        {
            throw new \InvalidArgumentException(
                '["auth"]["email/phone"] parameter is needed'
            );
        }
        if (FALSE === isset($configParams['auth']['password']))
        {
            throw new \InvalidArgumentException(
                '["auth"]["password"] parameter is needed'
            );
        }
        if (FALSE === isset($configParams['auth']['region']))
        {
            throw new \InvalidArgumentException(
                '["auth"]["region"] parameter is needed'
            );
        }

        // Optional library parameters
        if (isset($configParams['settings']['token'])) {
            $config['settings']['token'] = $configParams['settings']['token'];
        }
        if (isset($configParams['settings']['allowed_ips'])) {
            $config['settings']['allowed_ips'] = $configParams['settings']['allowed_ips'];
        }
        if (isset($configParams['settings']['cachedir'])) {
            $config['settings']['cachedir'] = $configParams['settings']['cachedir'];
        }
        if (isset($configParams['settings']['cachetime'])) {
            $config['settings']['cachetime'] = $configParams['settings']['cachetime'];
        }

        $this->setConstructor($config);
    }

    // General setter
    private function setConstructor($config)
    {
        // Default paramas
        foreach ($config as $key => $value)
        {
            $this->setConfig($key, $value);
        }
    }

    /**
     * @param string $param
     * @return mixed
     */
    public function getConfig($param) {
        return @$this->{$param};
    }

    /**
     * @param string $param
     * @param mixed $value
     */
    public function setConfig($param, $value) {
        $this->{$param} = $value;
    }

    /**
     * @param string $param
     * @return bool
     */
    public function hasConfig($param) {
        return (is_object(@$this->{$param}) || isset($this->{$param}) ? TRUE : FALSE);
    }

}
