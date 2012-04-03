<?php

namespace ZendService\OAuth2\Options;

use ZendService\OAuth2\Exception,
    Zend\Stdlib\Options;

/**
 * @property StandardOption $code
 * @property StandardOption $clientId
 * @property StandardOption $clientSecret
 * @property StandardOption $redirectUri
 * @property StandardOption $grantType
 */
class Stage2Options extends BaseAbstract
{
    /**
     * @var StandardOption
     */
    protected $code;
    /**
     * @var StandardOption
     */
    protected $clientId;
    /**
     * @var StandardOption
     */
    protected $clientSecret;
    /**
     * @var StandardOption
     */
    protected $redirectUri;
    /**
     * @var StandardOption
     */
    protected $grantType;

    protected function setDefaultOptions()
    {
        $this->code = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'code',
        ));
        $this->clientId = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'client_id'
        ));
        $this->clientSecret = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'client_secret',
        ));
        $this->redirectUri = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'redirect_uri'
        ));
        $this->grantType = new StandardOption(array(
            'defaultValue'  => 'authorization_code',
            'accessKey'     => 'grant_type',
        ));
    }

    /**
     * Getter
     * @return StandardOption
     */
    protected function getCode()
    {
        return $this->code;
    }
    /**
     * Getter
     * @return StandardOption
     */
    protected function getClientId()
    {
        return $this->clientId;
    }
    /**
     * Getter
     * @return StandardOption
     */
    protected function getClientSecret()
    {
        return $this->clientSecret;
    }
    /**
     * Getter
     * @return StandardOption
     */
    protected function getRedirectUri()
    {
        return $this->redirectUri;
    }
    /**
     * Getter
     * @return StandardOption
     */
    protected function getGrantType()
    {
        return $this->grantType;
    }
}
