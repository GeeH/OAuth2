ZendService\0Auth 2.0
=====================

Installing
----------
Checkout this repository to your *vendor* directory, and add as a module in your `application.config.php`

Using
-----
Instanciate a new OAuth2 (probably in a controller) passing your client secret, client id, the request object, response object and an optional config file to use. You'll possibly want to set the *stage1* OAuth2Options object.
If no option object is passed it will default to the standard latest draft options. Currently only works with Google and Facebook by default, but expect more vendor config files shortly.

To use authenticate with Facebook (in this instance from a controller):

`
<?php

namespace Application\Controller;

use Zend\Mvc\Controller\ActionController;
use Zend\View\Model\ViewModel;
use OAuth2\OAuth2;
use OAuth2\Options\Vendor;

class IndexController extends ActionController
{
    public function indexAction()
    {
        $OAuth2 = new OAuth2(
            '113017628724890',
            'b07b3178d54ac9117bd8067ca0a82696',
            $this->getRequest(),
            $this->getResponse(),
            new Vendor\FacebookOptions()
        );
        $token = $OAuth2->getToken();
        die($token);
        return new ViewModel();
    }
}
`