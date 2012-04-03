<?php
namespace ZendService\OAuth2\Options\Vendors;

use ZendService\OAuth2\Options;

class GoogleOptions extends Options\OAuth2Options
{
    public function __construct()
    {
        parent::__construct();
        $this->vendorOptions = new VendorOptions(array(
            'authEntryUri'      => 'https://accounts.google.com/o/oauth2/auth',
            'tokenEntryUri'     => 'https://accounts.google.com/o/oauth2/token',
            'responseFormat'    => 'json',
            'headers'           => array('GData-Version' => '3.0'),
        ));
    }
}
