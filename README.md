ZendService\0Auth 2.0
=====================

Installing
----------
Checkout this repository to your *vendor* directory, and setup autoloading for ZendService\OAuth2 pointing to vendor/ZendServiceOAuth2/src/ZendService/OAuth2

    $loader = new StandardAutoloader();
    $loader->registerNamespace('ZendService\OAuth2', 'vendor/ZendServiceOAuth2/src/ZendService/OAuth2')->register();

Using
-----
Instanciate a new OAuth2 (probably in a controller) passing your client secret, client id, the request object and an optional config file to use. You'll possibly want to set the *stage1* OAuth2Options object.
If no option object is passed it will default to the default options. Currently only works with Google as no setters for the options you'll need for other vendors.

To use authenticate with Google:

    $auth = new OAuth2(
                '<Your app client id>',
                '<Your app secret>',
                $this->getRequest(),
                new Vendors\GoogleOptions(),
            );
    $auth->setScope('https://www.googleapis.com/auth/userinfo.profile');
    $token = $auth->getToken(true);

