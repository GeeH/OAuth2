<?php
namespace ZendService\OAuth2;

use Zend\Loader,
    Zend\Config,
    Zend\Session,
    Zend\Json,
    Zend\Http\PhpEnvironment\Request as Request,
    Zend\Http\Client as HttpClient,
    ZendService\OAuth2\Exception\OAuth2Exception;

class OAuth2
{
    /**
     * @var array
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
    public function __construct($clientId, $clientSecret, Request $request, $config = null)
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
     * Sets a config value
     * @param string $section The section of config
     * @param string $key The config key
     * @param mixed $value The config value (usually an array)
     * @return OAuth2
     */
    public function setConfigValue($section, $key, $value)
    {
        $this->config[$section][$key] = $value;
        return $this;
    }

    /**
     * Gets a config value
     * @param string $section The section of config
     * @param string $key The config key
     * @return mixed
     */
    public function getConfigValue($section, $key)
    {
        return $this->config[$section][$key];
    }

    /**
     * Returns the key name if overloaded in config
     * @param string $section The section of config
     * @param string $key The config key
     * @return string
     */
    public function getConfigKeyName($section, $key)
    {
        $param = $this->config[$section][$key];
        return $param[1];
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
        $httpClient = new HttpClient($this->config['config']['tokenEntryUri']);
        $httpClient->setMethod('POST');
        $params = array();
        foreach($this->config['stage2'] as $key => $param)
        {
            $param = $this->getConfigValue('stage2', $key);
            if($param[1] === 'code')
            {
                $param[0] = $code;
            }
            if(empty($param[0]))
            {
                $param[0] = $this->getDefaultParam($key);
            }
            $params[$param[1]] = $param[0];

        }
        $httpClient->setParameterPost($params);
        if(isset($this->config['headers']))
        {
            $httpClient->setHeaders($this->config['headers']);
        }
        $content = $httpClient->send()->getContent();
        if(isset($this->config['config']['responseFormat'])
            && $this->config['config']['responseFormat'] === 'urlencode')
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
        if($this->request->query()->offsetExists($this->getConfigKeyName('stage1', 'state')))
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
     * @param null $config
     * @return OAuth2
     * @throws Exception\OAuth2Exception
     */
    protected function setConfig($config = null)
    {
        if(is_string($config))
        {
            $configFile = "vendor/ZendServiceOAuth2/config/{$config}.config.php";
            if(!Loader::isReadable($configFile))
            {
                throw new OAuth2Exception("Invalid config supplied \"$configFile\"");
            }
            $this->config = Config\Factory::fromFiles(array(
                'vendor/ZendServiceOAuth2/config/default.config.php',
                $configFile
            ));
        }
        else
        {
            $this->config = Config\Factory::fromFile('vendor/ZendServiceOAuth2/config/default.config.php');
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
        if($query->offsetExists($this->getConfigKeyName('stage1Response', 'error')))
        {
            throw new OAuth2Exception('Error gaining authorisation: '.$query->get($this->getConfigKeyName('stage1Response', 'error')));
        }
        if($query->offsetExists($this->getConfigKeyName('stage1Response', 'code')))
        {
            if($query->get($this->getConfigKeyName('stage1Response', 'state')) !== $this->session->state)
            {
                throw new OAuth2Exception('Error gaining authorisation: state mismatch');
            }
            return $query->get($this->getConfigKeyName('stage1Response', 'code'));
        }

        return null;
    }

    /**
     * Redirects browser to vendor auth uri
     * @todo Handle redirect better?
     */
    public function getCodeFromVendor()
    {
        $uri = $this->config['config']['authEntryUri'];
        $params = '';
        foreach($this->config['stage1'] as $key => $param)
        {
            $param = $this->getConfigValue('stage1', $key);
            if(empty($param[0]))
            {
                $param[0] = $this->getDefaultParam($key);
            }
            if($key === 'state')
            {
                $this->session->state = $param[0];
            }
            $params .= '&'.$param[1].'='.$param[0];
        }
        $uri .= '?'.ltrim($params, '&');
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
        $expiresIn =  $this->getConfigKeyName('stage2Response', $key);
        if(is_object($response) && property_exists($response, $expiresIn))
        {
            return $response->{$expiresIn};
        }
        if(is_array($response) && array_key_exists($expiresIn, $response))
        {
            return $response[$expiresIn];
        }
        throw new OAuth2Exception("Expire time does not exist as key \"{$expiresIn}\"");
    }

    /**
     * Is the given key in the response?
     * @param string $response
     * @param string $key
     * @return bool
     */
    public function isInResponse($response, $key)
    {
        $expiresIn =  $this->getConfigKeyName('stage2Response', $key);
        if(is_object($response) && property_exists($response, $expiresIn))
        {
            return true;
        }
        if(is_array($response) && array_key_exists($expiresIn, $response))
        {
            return true;
        }
        return false;
    }
}
