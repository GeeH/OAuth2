<?php
namespace OAuth2\Options\Vendor;

use OAuth2\Options;

class FacebookOptions extends Options\OAuth2Options
{
    /**
     * Constructor
     *
     * @return FacebookOptions
     */
    public function __construct()
    {
        parent::__construct();

        $this->vendorOptions = new VendorOptions(array(
            'authEntryUri'      => 'https://www.facebook.com/dialog/oauth',
            'tokenEntryUri'     => 'https://graph.facebook.com/oauth/access_token',
            'responseFormat'    => 'urlencode',
            'headers'           => array(),
        ));

        $this->stage2Response->expiresIn->accessKey = 'expires';

        return $this;
    }
}
