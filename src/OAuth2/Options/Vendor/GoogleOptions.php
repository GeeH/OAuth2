<?php
namespace OAuth2\Options\Vendor;

use OAuth2\Options;

class GoogleOptions extends Options\OAuth2Options
{
    /**
     * Constructor
     *
     * @return GoogleOptions
     */
    public function __construct()
    {
        parent::__construct();

        $this->vendorOptions = new VendorOptions(array(
            'authEntryUri'      => 'https://accounts.google.com/o/oauth2/auth',
            'tokenEntryUri'     => 'https://accounts.google.com/o/oauth2/token',
            'responseFormat'    => 'json',
            'headers'           => array('GData-Version' => '3.0'),
        ));

        $this->stage1->accessType->defaultValue = 'offline';

        return $this;
    }
}
