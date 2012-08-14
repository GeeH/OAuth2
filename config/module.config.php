<?php
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use OAuth2\OAuth2;

return array(
    'Spabby\OAuth2' => array(
        'clientId' => '113017628724890',
        'clientSecret' => 'b07b3178d54ac9117bd8067ca0a82696',
        'vendorOptions' => 'OAuth2\Options\Vendor\FacebookOptions',
    ),
    'service_manager' => array(
        'factories' => array(
            'Spabby\OAuth2' => function($sm)
            {
                $config = $sm->get('config');
                if(!array_key_exists('Spabby\OAuth2', $config)) {
                      throw new ServiceNotCreatedException("Missing config key for Spabby\\Oauth");
                }
                $OAuth2 = new OAuth2(
                    $config['Spabby\OAuth2']['clientId'],
                    $config['Spabby\OAuth2']['clientSecret'],
                    $sm->get('request'),
                    $sm->get('response'),
                    new $config['Spabby\OAuth2']['vendorOptions']()
                );
                return $OAuth2;
            }
        ),
    ),
);

