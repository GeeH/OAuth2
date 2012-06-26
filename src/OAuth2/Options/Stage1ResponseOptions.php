<?php

namespace OAuth2\Options;

use OAuth2\Exception,
    Zend\Stdlib\Options;

/**
 * @property StandardOption $error
 * @property StandardOption $state
 * @property StandardOption $code
 */
class Stage1ResponseOptions extends BaseAbstract
{
    /**
     * @var Options\StandardOption
     */
    protected $error;

    /**
     * @var Options\StandardOption
     */
    protected $state;

    /**
     * @var Options\StandardOption
     */
    protected $code;

    /**
     * Set Default Options
     *
     * @return void
     */
    protected function setDefaultOptions()
    {
        $this->error = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'error'
        ));
        $this->state = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'state',
        ));
        $this->code = new StandardOption(array(
            'defaultValue'  => '',
            'accessKey'     => 'code',
        ));
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

    /**
     * Getter
     *
     * @return StandardOption
     */
    protected function getState()
    {
        return $this->state;
    }

    /**
     * Getter
     *
     * @return StandardOption
     */
    protected function getCode()
    {
        return $this->code;
    }
}
