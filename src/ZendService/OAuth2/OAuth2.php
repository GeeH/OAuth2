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
    protected $options;
    
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
     *
     * @param string $clientId  Unique vendor client id
     * @param string $clientSecret  Vendor secret
     * @param \Zend\Http\PhpEnvironment\Request $request Request object
     * @param null $options Vendor specific config file
     */
    public function __construct($clientId, $clientSecret, Request $request, OAuth2Options $options = null)
    {
        if(empty($clientId) || empty($clientSecret)) {
            throw new OAuth2Exception('clientId and clientSecret cannot be empty');
        }
        $this->setClientId($clientId);
        $this->setClientSecret($clientSecret);
        $this->setOptions($options);
        $this->setRequest($request);
        $this->session = new Session\Container('ZendService\OAuth2');
    }

    /**
     * Sets client secret
     *
     * @param string $clientSecret
     * @return OAuth2
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = urlencode($clientSecret);
        return $this;
    }

    /**
     * Sets client id
     *
     * @param string $clientId
     * @return OAuth2
     */
    public function setClientId($clientId)
    {
        $this->clientId = urlencode($clientId);
        return $this;
    }

    /**
     * Sets request object
     *
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
     * 
     * @param string $scope
     * @return OAuth2
     */
    public function setScope($scope)
    {
        $this->options->stage1->scope = $scope;
        return $this;
    }



    /**
     * Gets a valid OAuth2.0 access token
     *
     * @param bool $forceNewToken
     * @return string
     */
    public function getToken($forceNewToken=false)
    {
        if($this->session->offsetExists('accessToken')
            && $this->session->offsetExists('expiryTime')
            && is_string($this->session->accessToken)
            && $this->session->expiryTime > time()
            && !$forceNewToken) {
            return $this->session->accessToken;
        }
        $code = $this->getCode();
        $httpClient = new HttpClient($this->options->vendorOptions->tokenEntryUri);
        $httpClient->setMethod('POST');
        $params = array();
        foreach($this->options->stage2->toArray() as $key => $param) {
            if($key === 'code') {
                $param = urlencode($code);
            }
            if(empty($param)) {
                $param = $this->getDefaultParam($key);
            }
            $params[$key] = $param;
        }
        $httpClient->setParameterPost($params);
        if(is_array($this->options->vendorOptions->headers)) {
            $httpClient->setHeaders($this->options->vendorOptions->headers);
        }
        $content = $httpClient->send()->getContent();
        if($this->options->vendorOptions->responseFormat === 'urlencode') {
            try {
                $response = Json\Decoder::decode($content);
            }
            catch(\Zend\Json\Exception\RuntimeException $e) {
                if($e->getMessage() !== 'Illegal Token') {
                    throw new OAuth2Exception('Error decoding Json: '.$e->getMessage());
                }
                parse_str($content, $response);
            }
        } else {
            $response = Json\Decoder::decode($httpClient->send()->getContent());
        }

        if($this->isInResponse($response, 'error')) {
            $error = $this->getFromResponse($response, 'error');
            if(is_object($error)
                && method_exists($error, 'type')
                && method_exists($error, 'code')
                && method_exists($error, 'message')) {
                throw new OAuth2Exception("{$error->type} ({$error->code}): {$error->message}");
            } else {
                if(!is_string($error)) {
                    $error = Json\Encoder::encode($error);
                }

                throw new OAuth2Exception("Error returned from vendor: {$error}");
            }

        }
        $expires = $this->getFromResponse($response, 'expiresIn');
        $token = $this->getFromResponse($response, 'accessToken');
        $this->session->expiryTime = $expires+time();
        $this->session->accessToken = $token;
        return $token;
    }

    /**
     * Gets a code either from request object, or from vendor
     *
     * @return null/string
     */
    public function getCode()
    {
        if($this->request->query()->offsetExists($this->options->stage1->state->accessKey)) {
            $code = $this->getCodeFromRequest();
            if(is_string($code)) {
                return $code;
            }
        }
        $this->getCodeFromVendor();
    }

    /**
     * Sets the and merges config files
     *
     * @param null/OAuth2Options $config
     * @return OAuth2
     * @throws Exception\OAuth2Exception
     */
    protected function setOptions($options = null)
    {
        if ($options === null) {
            $this->options = new OAuth2Options();
        } else if ($options instanceof OAuth2Options) {
            $this->options = $options;
        } else {
            throw new OAuth2Exception('$options must be null or an OAuth2Options instance');
        }
        return $this;
    }

    /**
     * Grabs some default values for parameters
     *
     * @param string $key
     * @return string
     */
    protected function getDefaultParam($key)
    {
        switch($key) {
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
     *
     * @return string/null
     * @throws Exception\OAuth2Exception
     */
    public function getCodeFromRequest()
    {
        $query = $this->request->query();
        if($query->offsetExists($this->options->stage1Response->error->accessKey)) {
            throw new OAuth2Exception('Error gaining authorisation: '.$query->get($this->options->stage1Response->error->accessKey));
        }
        if($query->offsetExists($this->options->stage1Response->code->accessKey)) {
            if($query->get($this->options->stage1Response->state->accessKey) !== $this->session->state) {
                throw new OAuth2Exception('Error gaining authorisation: state mismatch');
            }
            return $query->get($this->options->stage1Response->code->accessKey);
        }
        return null;
    }

    /**
     * Redirects browser to vendor auth uri
     *
     * @todo Handle redirect better?
     */
    public function getCodeFromVendor()
    {
        $uri = $this->options->vendorOptions->authEntryUri;
        $params = '';
        foreach($this->options->stage1->toArray() as $key => $param) {
            if(empty($param)) {
                $param = $this->getDefaultParam($key);
            }
            $params[$key] = $param;
        }
        $this->session->state = $params[$this->options->stage1->state->accessKey];
        $uri .= '?'.http_build_query($params);
        header("location: {$uri}");
        die();
    }

    /**
     * Gets a given key from a http response
     *
     * @param mixed $response
     * @param $key
     * @return mixed
     * @throws Exception\OAuth2Exception
     */
    public function getFromResponse($response, $key)
    {
        $keyName =  $this->options->stage2Response->{$key}->accessKey;
        if(is_object($response) && property_exists($response, $keyName)) {
            return $response->{$keyName};
        }
        if(is_array($response) && array_key_exists($keyName, $response)) {
            return $response[$keyName];
        }
        throw new OAuth2Exception("Key \"$key\" does not exist as key \"{$keyName}\"");
    }

    /**
     * Is the given key in the response?
     *
     * @param string $response
     * @param string $key
     * @return bool
     */
    public function isInResponse($response, $key)
    {
        $keyName =  $this->options->stage2Response->{$key}->accessKey;
        if(is_object($response) && property_exists($response, $keyName)) {
            return true;
        }
        if(is_array($response) && array_key_exists($keyName, $response)) {
            return true;
        }
        return false;
    }
}
