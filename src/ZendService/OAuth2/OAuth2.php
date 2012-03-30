<?php
namespace ZendService\OAuth2;

use Zend\Loader,
    Zend\Config,
    Zend\Session,
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
    public function getToken()
    {
        if($this->session->offsetExists('token') && $this->session->expires > time())
        {
            return $this->session->token;
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
        $response = \Zend\Json\Decoder::decode($httpClient->send()->getContent());
        var_dump($response);
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
}