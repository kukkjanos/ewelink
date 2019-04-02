# eWeLink Smart Home PHP Library

## Installation using [Composer]

```bash
$ composer require kukkjanos/ewelink
```

```
<?php

require_once __DIR__ . '/vendor/autoload.php';

$options = [
    'auth' => [
        'email'    => 'eWelink login email',
        //'phone'    => '+361234567', # email or phone login parameter
        'password' => 'eWelink login password',
        'region'   => 'eu'
    ],
    'settings' => [
        'token'        => 'abc', // (not working for now)
        'allowed_ips'  => ['x.x.x.x'], // Allowed ip address (not working for now)
        'cachedir' => './cache', // Token cache directory
        'cachetime' => 3600, // The expiration time, defaults to 3600
        
    ]
];

// Init configuration
$config = new EWeLink\Api\Config($options);

// Init API
$api = new EWeLink\Api\EWeApi($config);

// All device
print_r( $api->getDevices() );

// One device
$deviceId = 'xyz';
print_r( $api->getDevice($deviceId) );

// Toogle device
//print_r( $api->toggleDevice($deviceId) );

```