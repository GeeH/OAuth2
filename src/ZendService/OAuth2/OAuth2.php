<?php
namespace ZendService\OAuth2;

use Zend\Loader,
    Zend\Config,
    Zend\Session,
    Zend\Json,
    Zend\Http\PhpEnvironment\Request as Request,
    Zend\Http\Client as HttpClient,
    ZendService\OAuth2\Options\OAuth2Options,
    ZendService\OAuth2\Exception\OAuth2Exception;

class OAuth2
{
    /**
     * @var OAuth2Options
     */
    protected $config;
    /**
     * @var Session\Container
     */
    protected $session;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * Constructor
     * @param string $clientId  Unique vendor client id
     * @param string $clientSecret  Vendor secret
     * @param \Zend\Http\PhpEnvironment\Request $request Request object
     * @param null $config Vendor specific config file
     */
    public function __construct($clientId, $clientSecret, Request $request, OAuth2Options $config = null)
    {
        if(empty($clientId) || empty($clientSecret))
        {
            throw new OAuth2Exception('clientId and clientSecret cannot be empty');
        }
        $this->setClientId($clientId);
        $this->setClientSecret($clientSecret);
        $this->setConfig($config);
        $this->setRequest($request);
        $this->session = new Session\Container('ZendService\OAuth2');
    }

    /**
     * Sets client secret
     * @param string $clientSecret
     * @return OAuth2
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    /**
     * Sets client id
     * @param string $clientId
     * @return OAuth2
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * Sets request object
     * @param \Zend\Http\PhpEnvironment\Request $request
     * @return OAuth2
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Set scope of app
     * @param string $scope
     * @return OAuth2
     */
    public function setScope($scope)
    {
        $this->config->stage1->scope = $scope;
        return $this;
    }



    /**
     * Gets a valid OAuth2.0 access token
     * @return string
     */
    public function getToken($forceNewToken=false)
    {
        if($this->session->offsetExists('accessToken')
            && $this->session->offsetExists('expiryTime')
            && is_string($this->session->accessToken)
            && $this->session->expiryTime > time()
            && !$forceNewToken)
        {
            return $this->session->accessToken;
        }
        $code = $this->getCode();
        $httpClient = new HttpClient($this->config->vendorOptions->tokenEntryUri);
        $httpClient->setMethod('POST');
        $params = array();
        foreach($this->config->stage2->toArray() as $key => $param)
        {
            if($key === 'code')
            {
                $param = $code;
            }
            if(empty($param))
            {
                $param = $this->getDefaultParam($key);
            }
            $params[$key] = $param;
        }
        $httpClient->setParameterPost($params);
        if(is_array($this->config->vendorOptions->headers))
        {
            $httpClient->setHeaders($this->config->vendorOptions->headers);
        }
        $content = $httpClient->send()->getContent();
        if($this->config->vendorOptions->responseFormat === 'urlencode')
        {
            try
            {
                $response = Json\Decoder::decode($content);
            }
            catch(\Zend\Json\Exception\RuntimeException $e)
            {
                if($e->getMessage() !== 'Illegal Token')
                {
                    throw new OAuth2Exception('Error decoding Json: '.$e->getMessage());
                }
                parse_str($content, $response);
            }
        }
        else
        {
            $response = Json\Decoder::decode($httpClient->send()->getContent());
        }

        if($this->isInResponse($response, 'error'))
        {
            $error = $this->getFromResponse($response, 'error');
            if(is_object($error)
                && method_exists($error, 'type')
                && method_exists($error, 'code')
                && method_exists($error, 'message'))
            {
                throw new OAuth2Exception("{$error->type} ({$error->code}): {$error->message}");
            }
            else
            {
                if(!is_string($error))
                {
                    $error = Json\Encoder::encode($error);
                }

                throw new OAuth2Exception("Error returned from vendor: {$error}");
            }

        }
        $expires = $this->getFromResponse($response, 'expires_in');
        $token = $this->getFromResponse($response, 'access_token');
        $this->session->expiryTime = $expires+time();
        $this->session->accessToken = $token;
        return $token;
    }

    /**
     * Gets a code either from request object, or from vendor
     * @return null/string
     */
    public function getCode()
    {
        if($this->request->query()->offsetExists($this->config->stage1->state->accessKey))
        {
            $code = $this->getCodeFromRequest();
            if(is_string($code))
            {
                return $code;
            }
        }
        $this->getCodeFromVendor();
    }

    /**
     * Sets the and merges config files
     * @param null/OAuth2Options $config
     * @return OAuth2
     * @throws Exception\OAuth2Exception
     */
    protected function setConfig($config = null)
    {
        if($config instanceof OAuth2Options)
        {
             $this->config = $config;
        }
        else
        {
            $this->config = new OAuth2Options();
        }
        return $this;
    }

    /**
     * Grabs some default values for parameters
     * @param string $key
     * @return string
     */
    protected function getDefaultParam($key)
    {
        switch($key)
        {
            case 'client_id':
                return $this->clientId;
                break;
            case 'client_secret':
                return $this->clientSecret;
                break;
            case 'scope':
                return '';
                break;
            case 'state':
                return 'MustAddRandomStringGenerationHere';
                break;
            case 'redirect_uri':
                return 'http://'.$_SERVER['HTTP_HOST'].'/';
                break;
            case 'approval_prompt':
                return 'auto';
                break;
            default:
                var_dump($key);
                break;
        }
    }

    /**
     * Grabs the 1st stage code from the request object
     * @return string/null
     * @throws Exception\OAuth2Exception
     */
    public function getCodeFromRequest()
    {
        $query = $this->request->query();
        if($query->offsetExists($this->config->stage1Response->error->accessKey))
        {
            throw new OAuth2Exception('Error gaining authorisation: '.$query->get($this->config->stage1Response->error->accessKey));
        }
        if($query->offsetExists($this->config->stage1Response->code->accessKey))
        {
            if($query->get($this->config->stage1Response->state->accessKey) !== $this->session->state)
            {
                throw new OAuth2Exception('Error gaining authorisation: state mismatch');
            }
            return $query->get($this->config->stage1Response->code->accessKey);
        }
        return null;
    }

    /**
     * Redirects browser to vendor auth uri
     * @todo Handle redirect better?
     */
    public function getCodeFromVendor()
    {
        $uri = $this->config->vendorOptions->authEntryUri;
        $params = '';
        foreach($this->config->stage1->toArray() as $key => $param)
        {
            if(empty($param))
            {
                $param = $this->getDefaultParam($key);
            }
            $params[$key] = $param;
        }
        $this->session->state = $params[$this->config->stage1->state->accessKey];
        $uri .= '?'.http_build_query($params);
        header("location: {$uri}");
        die();
    }

    /**
     * Gets the expires_in key
     * @param mixed $response
     * @return mixed
     * @throws Exception\OAuth2Exception
     */
    public function getFromResponse($response, $key)
    {
        $keyName =  $this->config->stage2Response->{$key}->accessKey;
        if(is_object($response) && property_exists($response, $keyName))
        {
            return $response->{$keyName};
        }
        if(is_array($response) && array_key_exists($keyName, $response))
        {
            return $response[$keyName];
        }
        throw new OAuth2Exception("Key \"$key\" does not exist as key \"{$keyName}\"");
    }

    /**
     * Is the given key in the response?
     * @param string $response
     * @param string $key
     * @return bool
     */
    public function isInResponse($response, $key)
    {
        $keyName =  $this->config->stage2Response->{$key}->accessKey;
        if(is_object($response) && property_exists($response, $keyName))
        {
            return true;
        }
        if(is_array($response) && array_key_exists($keyName, $response))
        {
            return true;
        }
        return false;
    }
}
