<?php
return array(
    'stage1' => array(
        'response_type'     => array('code', 'response_type'),
        'client_id'         => array('', 'client_id'),
        'redirect_uri'      => array('', 'redirect_uri'),
        'scope'             => array('', 'scope'),
        'state'             => array('', 'state'),
        'access_type'       => array('online', 'access_type'),
        'approval_prompt'   => array('auto', 'approval_prompt'),
    ),
    'stage1Response' => array(
        'error'             => array('', 'error'),
        'state'             => array('', 'state'),
        'code'              => array('', 'code'),
    ),
    'stage2' => array(
        'code'              => array('', 'code'),
        'client_id'         => array('', 'client_id'),
        'client_secret'     => array('', 'client_secret'),
        'redirect_uri'      => array('', 'redirect_uri'),
        'grant_type'        => array('authorization_code', 'grant_type'),
    ),
    'stage2Response' => array(
        'access_token'      => array('', 'access_token'),
        'refresh_token'     => array('', 'refresh_token'),
        'expires_in'        => array('', 'expires_in'),
        'token_type'        => array('', 'token_type'),
    )
);