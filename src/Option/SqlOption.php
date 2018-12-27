<?php
namespace Haitun\Service\TpAdmin\Option;

use Haitun\Service\TpAdmin\System\Be;
use Haitun\Service\TpAdmin\System\Cache;

/**
 * SQL 配置项
 */
class SqlOption implements Option
{
    private $data = null;
    private $keyValues = null;

    private $md5Sign = null;

    public function __construct($data)
    {
        $this->data = trim($data);
        $this->md5Sign = md5($this->data);
    }

    public function getType() {
        return 'sql';
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
        if ($this->keyValues !== null ) return $this->keyValues;

        $cacheKey = 'Cache:SqlOption:'.$this->md5Sign;
        $cacheData = Cache::get($cacheKey);

        if ($cacheData) {
            $this->keyValues = $cacheData;
            return $cacheData;
        }

        $keyValues = array();
        $result = Be::getDb()->getArrays($this->data);

        foreach ($result as $x) {

            $x = array_values($x);

            if (count($x) == 1) {
                $keyValues[$x[0]] = $x[0];
                continue;
            }

            if (count($x) >= 2) {
                $keyValues[$x[0]] = $x[1];
            }
        }

        $this->keyValues = $keyValues;
        Cache::set($cacheKey, $keyValues, 600);

        return $keyValues;
    }

}