<?php

namespace ZendService\OAuth2\Options;

use ZendService\OAuth2\Exception,
    Zend\Stdlib\Options;

/**
 * @property StandardOption $responseType
 * @property StandardOption $clientId
 * @property StandardOption $redirectUri
 * @property StandardOption $scope
 * @property StandardOption $state
 * @property StandardOption $accessType
 * @property StandardOption $approvalPrompt
 */
class Stage1Options extends BaseAbstract
{
    /**
     * @var Options\StandardOption
     */
    protected $responseType;
    /**
     * @var Options\StandardOption
     */
    protected $clientId;
    /**
     * @var Options\StandardOption
     */
    protected $redirectUri;
    /**
     * @var Options\StandardOption
     */
    protected $scope;
    /**
     * @var Options\StandardOption
     */
    protected $state;
    /**
     * @var Options\StandardOption
     */
    protected $accessType;
    /**
     * @var Options\StandardOption
     */
    protected $approvalPrompt;

    protected function setDefaultOptions()
    {
        $this->responseType = new StandardOption(array(
            'defaultValue'  => 'code',
            'accessKey'     => 'response_type'
        ));
        $this->clientId = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'client_id',
        ));
        $this->redirectUri = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'redirect_uri',
        ));
        $this->scope = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'scope',
        ));
        $this->state = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'state',
        ));
        $this->accessType = new StandardOption(array(
            'defaultValue'  => 'online',
            'accessKey'     => 'access_type',
        ));
        $this->approvalPrompt = new StandardOption(array(
            'defaultValue'  => 'auto',
            'accessKey'     => 'approval_prompt',
        ));
    }

    /**
     * Getter
     * @return StandardOption
     */
    protected function getResponseType()
    {
        return $this->responseType;
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
    protected function getRedirectUri()
    {
        return $this->redirectUri;
    }
    /**
     * Getter
     * @return StandardOption
     */
    protected function getScope()
    {
        return $this->scope;
    }
    /**
     * Getter
     * @return StandardOption
     */
    protected function getState()
    {
        return $this->state;
    }
    /**
     * Getter
     * @return StandardOption
     */
    protected function getAccessType()
    {
        return $this->accessType;
    }
    /**
     * Getter
     * @return StandardOption
     */
    protected function getApprovalPrompt()
    {
        return $this->approvalPrompt;
    }

    /**
     * Setter
     * @param string $scope
     * @return Stage1Options
     */
    protected function setScope($scope)
    {
        $this->scope->defaultValue = $scope;
        return $this;
    }

}
