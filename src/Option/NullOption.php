<?php

namespace Haitun\Service\TpAdmin\Option;

/**
 * 数组配置项
 */
class NullOption implements Option
{

    private $data = null;
    private $keyValues = null;

    public function __construct($data)
    {
        $this->data = trim($data);
    }

    public function getType() {
        return 'null';
    }

    public function getData() {
        return '';
    }

    public function getKeys()
    {
        return array();
    }

    public function getValues()
    {
        return array();
    }

    public function getKeyValues()
    {
        return array();
    }

}