<?php
return array(
    'config' => array(
        'authEntryUri'      => 'https://www.facebook.com/dialog/oauth',
        'tokenEntryUri'     => 'https://graph.facebook.com/oauth/access_token',
        'responseFormat'    => 'urlencode'
    ),
    'stage2' => array(
        'code'              => array('', 'code'),
    ),
    'stage2Response' => array(
        'expires_in'        => array('', 'expires'),
    ),
);