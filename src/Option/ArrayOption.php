<?php

namespace Haitun\Service\TpAdmin\Option;

/**
 * 数组配置项
 */
class ArrayOption implements Option
{

    private $data = null;
    private $keyValues = null;

    public function __construct($data)
    {
        $this->data = trim($data);
    }

    public function getType() {
        return 'array';
    }

    public function getData() {
        return $this->data;
    }

    public function getKeys()
    {
        return array_keys($this->getKeyValues());
    }

    public function getValues()
    {
        return array_values($this->getKeyValues());
    }

    public function getKeyValues()
    {
        if ($this->keyValues !== null) return $this->keyValues;

        $data = explode("\n", $this->data);

        $keyValues = array();
        foreach ($data as $x) {
            $key = null;
            $val = null;
            $xs = explode(':', $x);
            if (count($xs) == 1) {
                $key = trim($xs[0]);
                $val = trim($xs[0]);
            } elseif (count($xs) >= 2) {

                $key = trim($xs[0]);
                $val = trim($xs[1]);
            }

            $keyValues[$key] = $val;
        }

        $this->keyValues = $keyValues;
        return $this->keyValues;
    }


    public function getKeyValuesString() {

    }
}