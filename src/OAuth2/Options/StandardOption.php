<?php

namespace OAuth2\Options;

use OAuth2\Exception,
    Zend\Stdlib\Options;

/**
 * @property string $defaultValue
 * @property string $accessKey
 */
class StandardOption extends Options
{
    /**
     * @var string the default value to return if none is set
     */
    protected $defaultValue;

    /**
     * @var string access key
     */
    protected $accessKey;

    /**
     * Setter
     *
     * @param string $value
     * @return StandardOption
     */
    public function setDefaultValue($value)
    {
        $this->defaultValue = $value;
        return $this;
    }

    /**
     * Setter
     *
     * @param string $accessKey
     * @return StandardOption
     */
    public function setAccessKey($accessKey)
    {
        $this->accessKey = $accessKey;
        return $this;
    }

    /**
     * Getter
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Getter
     * @return string
     */
    public function getAccessKey()
    {
        return $this->accessKey;
    }
}
