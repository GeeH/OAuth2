ZendService\0Auth 2.0
=====================

Installing
----------
Checkout this repository to your *vendor* directory, and setup autoloading for ZendService\OAuth2 pointing to vendor/ZendServiceOAuth2/src/ZendService/OAuth2

    $loader = new StandardAutoloader();
    $loader->registerNamespace('ZendService\OAuth2', 'vendor/ZendServiceOAuth2/src/ZendService/OAuth2')->register();

Using
-----
Instanciate a new OAuth2 (probably in a controller) passing your client secret, client id, the request object and an optional config file to use. You'll possibly want to set the *stage1* config variable *scope* using setConfigValue.

To use authenticate with Google:

    $auth = new OAuth2(
                '<Your app client id>',
                '<Your app secret>',
                $this->getRequest(),
                'google'
            );
    $auth->setConfigValue('stage1', 'scope', array('https://www.googleapis.com/auth/userinfo.profile', 'scope'));
    $token = $auth->getToken(true);

