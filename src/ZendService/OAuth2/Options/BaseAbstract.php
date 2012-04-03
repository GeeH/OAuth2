<?php
namespace ZendService\OAuth2\Options;

use ZendService\OAuth2\Exception,
Zend\Stdlib\Options;

class BaseAbstract extends Options
{

    public function __construct()
    {
        if(method_exists($this, 'setDefaultOptions'))
        {
            $this->setDefaultOptions();
        }
    }


    public function toArray()
    {
        $return = array();
        foreach($this as $key=>$val)
        {
            $return[$val->accessKey] = $val->defaultValue;
        }
        return $return;
    }
}
