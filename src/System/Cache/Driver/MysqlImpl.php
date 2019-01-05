<?php

namespace Haitun\Service\TpAdmin\System\Cache\Driver;

use Phpbe\System\Cache\CacheException;
use Haitun\Service\TpAdmin\System\Cache\Driver;
use Haitun\Service\TpAdmin\System\Be;

/**
 * Redis 缓存类
 *
    CREATE TABLE `cache` (
    `name` char(40) CHARACTER SET utf8 NOT NULL COMMENT '键名',
    `value` text CHARACTER SET utf8 NOT NULL COMMENT '值',
    `expire` int(11) NOT NULL COMMENT '超时时间'
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

    ALTER TABLE `cache`
    ADD PRIMARY KEY (`name`) USING HASH;
 *
 */
class MysqlImpl implements Driver
{

    protected $table = 'cache';

    /**
     * 构造函数
     *
     * @param array $options 初始化参数
     * @throws CacheException
     */
    public function __construct($options = array())
    {
        if (isset($options['table'])) {
            $this->table = $options['table'];
        }
    }

    /**
     * 获取 指定的缓存 值
     *
     * @param string $key 键名
     * @return mixed|false
     */
    public function get($key)
    {
        $key = sha1($key);

        if (rand(1, 99999) == 1 && (date('G') < 7 || date('G') > 22)) {
            Be::getDb()->execute('DELETE FROM `' . $this->table . '` WHERE `expire` < ' . time());
        }

        $value = Be::getDb()->getValue('SELECT `value` FROM `' . $this->table . '` WHERE `name`=\'' . $key . '\' AND `expire` > ' . time());
        if ($value === false) return false;
        if (is_numeric($value)) return $value;
        return unserialize($value);
    }

    /**
     * 获取 多个指定的缓存 值
     *
     * @param array $keys 键名 数组
     * @return array()
     */
    public function getMulti($keys)
    {
        foreach ($keys as &$key) {
            $key = sha1($key);
        }

        $return = array();

        $keyValues = Be::getDb()->getValues('SELECT `name`, `value` FROM `' . $this->table . '` WHERE `name` IN (\'' . implode('\',\'', $keys) . '\') AND `expire` > ' . time());

        foreach ($keys as $key) {

            $value = false;
            if (isset($keyValues[$key])) {
                $value = $keyValues[$key];
            }

            $return[] = $value;
        }

        return $return;
    }

    /**
     * 设置缓存
     *
     * @param string $key 键名
     * @param mixed $value 值
     * @param int $expire 有效时间（秒）
     * @return bool
     */
    public function set($key, $value, $expire = 86400000)
    {
        $key = sha1($key);

        if (!is_numeric($value)) $value = serialize($value);
        $expire = time() + $expire;

        $sql = 'INSERT INTO `' . $this->table . '`(`name`,`value`,`expire`) VALUES(\'' . $key . '\',\'' . $value . '\',' . $expire . ') ON DUPLICATE KEY UPDATE `value`=\'' . $value . '\',`expire`=' . $expire;
        Be::getDb()->execute($sql);
        return true;
    }


    /**
     * 设置缓存
     *
     * @param array $keyValues 键值对
     * @param int $expire 有效时间（秒）
     * @return bool
     */
    public function setMulti($keyValues, $expire = 86400000)
    {
        $expire = time() + $expire;
        foreach ($keyValues as $key => $value) {

            $key = sha1($key);

            if (!is_numeric($value)) {
                $value = serialize($value);
            }

            $sql = 'INSERT INTO `' . $this->table . '`(`name`,`value`,`expire`) VALUES(\'' . $key . '\',\'' . $value . '\',' . $expire . ') ON DUPLICATE KEY UPDATE `value`=\'' . $value . '\',`expire`=' . $expire;
            Be::getDb()->execute($sql);
        }

        return true;
    }

    /**
     * 指定键名的缓存是否存在
     *
     * @param string $key 缓存键名
     * @return bool
     */
    public function has($key)
    {
        $key = sha1($key);
        return Be::getDb()->getValue('SELECT COUNT(*) FROM `' . $this->table . '` WHERE `name`=\'' . $key . '\' AND `expire` > ' . time()) > 0;
    }

    /**
     * 删除指定键名的缓存
     *
     * @param string $key 缓存键名
     * @return bool
     */
    public function delete($key)
    {
        $key = sha1($key);
        Be::getDb()->execute('DELETE FROM `' . $this->table . '` WHERE `name`=\'' . $key . '\'');
        return true;
    }

    /**
     * 自增缓存（针对数值缓存）
     *
     * @param string $key 缓存变量名
     * @param int $step 步长
     * @return false|int
     */
    public function increment($key, $step = 1)
    {
        $key = sha1($key);
        $obj = Be::getDb()->getObject('SELECT * FROM `' . $this->table . '` WHERE `name`=\'' . $key . '\'');

        $value = null;
        if ($obj) {
            $value = $obj->value;
            if ($obj->expire < time()) {
                $value = $step;

                Be::getDb()->execute('UPDATE `' . $this->table . '` SET `value` = \''.$value.'\', `expire` = '.(time() + 86400000).' WHERE `name`=\'' . $key . '\'');
            } else {
                if (is_numeric($value)) {
                    $value += $step;
                } else {
                    $value = $step;
                }

                Be::getDb()->execute('UPDATE `' . $this->table . '` SET `value` = \''.$value.'\'  WHERE `name`=\'' . $key . '\'');
            }

        } else {

            $value = $step;
            $expire = time() + 86400000;

            $sql = 'INSERT INTO `' . $this->table . '`(`name`,`value`,`expire`) VALUES(\'' . $key . '\',\'' . $value . '\',' . $expire . ')';
            Be::getDb()->execute($sql);
        }

        return $value;
    }

    /**
     * 自减缓存（针对数值缓存）
     *
     * @param string $key 缓存变量名
     * @param int $step 步长
     * @return false|int
     */
    public function decrement($key, $step = 1)
    {
        $key = sha1($key);
        $obj = Be::getDb()->getObject('SELECT * FROM `' . $this->table . '` WHERE `name`=\'' . $key . '\'');

        $value = null;
        if ($obj) {
            $value = $obj->value;
            if ($obj->expire < time()) {
                $value = -$step;

                Be::getDb()->execute('UPDATE `' . $this->table . '` SET `value` = \''.$value.'\', `expire` = '.(time() + 86400000).' WHERE `name`=\'' . $key . '\'');
            } else {
                if (is_numeric($value)) {
                    $value -= $step;
                } else {
                    $value = -$step;
                }

                Be::getDb()->execute('UPDATE `' . $this->table . '` SET `value` = \''.$value.'\'  WHERE `name`=\'' . $key . '\'');
            }

        } else {

            $value = -$step;
            $expire = time() + 86400000;

            $sql = 'INSERT INTO `' . $this->table . '`(`name`,`value`,`expire`) VALUES(\'' . $key . '\',\'' . $value . '\',' . $expire . ')';
            Be::getDb()->execute($sql);
        }

        return $value;
    }

    /**
     * 清除缓存
     *
     * @return bool
     */
    public function flush()
    {
        Be::getDb()->execute('DELETE FROM `' . $this->table . '`');
        return true;
    }

}
