<?php

namespace OAuth2\Options;

use OAuth2\Exception;
use Zend\Stdlib\AbstractOptions;

abstract class BaseAbstract extends AbstractOptions
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
            if($val instanceof \OAuth2\Options\StandardOption) {
                $return[$val->accessKey] = $val->defaultValue;
            }
        }
        return $return;
    }
}
