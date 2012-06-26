<?php

namespace OAuth2\Options;

use OAuth2\Exception;
use Zend\Stdlib\Options;

abstract class BaseAbstract extends Options
{
    /**
     * Constructor
     *
     * @return BaseAbstract
     */
    public function __construct()
    {
        if(method_exists($this, 'setDefaultOptions')) {
            $this->setDefaultOptions();
        }
    }

    /**
     * To Array
     *
     * @return array
     */
    public function toArray()
    {
        $return = array();
        foreach($this as $key=>$val) {
            $return[$val->accessKey] = $val->defaultValue;
        }
        return $return;
    }
}
