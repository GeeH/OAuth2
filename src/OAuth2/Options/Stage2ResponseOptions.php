<?php

namespace OAuth2\Options;

use OAuth2\Exception,
    Zend\Stdlib\Options;

/**
 * @property StandardOption $accessToken
 * @property StandardOption $expiresIn
 * @property StandardOption $tokenType
 * @property StandardOption $error
 */
class Stage2ResponseOptions extends BaseAbstract
{
    /**
     * @var Options\StandardOption
     */
    protected $accessToken;

    /**
     * @var Options\StandardOption
     */
    protected $expiresIn;

    /**
     * @var Options\StandardOption
     */
    protected $tokenType;

    /**
     * @var Options\StandardOption
     */
    protected $error;

    /**
     * Set Default Options
     *
     * @return void
     */
    protected function setDefaultOptions()
    {
        $this->accessToken = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'access_token',
        ));
        $this->expiresIn = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'expires_in',
        ));
        $this->tokenType = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'token_type',
        ));
        $this->error = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'error'
        ));
    }

    /**
     * Getter
     *
     * @return StandardOption
     */
    protected function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Getter
     *
     * @return StandardOption
     */
    protected function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * Getter
     *
     * @return StandardOption
     */
    protected function getTokenType()
    {
        return $this->tokenType;
    }

    /**
     * Getter
     *
     * @return StandardOption
     */
    protected function getError()
    {
        return $this->error;
    }
}
