<?php
namespace Haitun\Service\TpAdmin\System;


/**
 *  资源工厂
 * @package System
 *
 */
abstract class Be
{

    private static $cache = array(); // 缓存资源实例

    /**
     * @var Runtime
     */
    private static $runtime = null; // 系统运行时


    /**
     * 获取数据库对象
     *
     * @param string $db 数据库名
     * @return \Haitun\Service\TpAdmin\System\Db\Driver
     * @throws \Exception
     */
    public static function getDb()
    {
        $key = 'Db';
        if (isset(self::$cache[$key])) return self::$cache[$key];

        $config = self::$runtime->getDbConfig();

        $class = 'Haitun\\Service\\TpAdmin\\System\\Db\\Driver\\MysqlImpl';
        self::$cache[$key] = new $class($config);
        return self::$cache[$key];
    }

    /**
     * 获取指定的一个服务
     *
     * @param string $service 服务名
     * @return Service | mixed
     * @throws \Exception
     */
    public static function getService($service)
    {
        $class = 'Haitun\\Service\\TpAdmin\\Service\\' . $service;
        if (isset(self::$cache[$class])) return self::$cache[$class];

        if (!class_exists($class)) throw new \Exception('服务 ' . $service . ' 不存在！');

        self::$cache[$class] = new $class();
        return self::$cache[$class];
    }

    /**
     * 获取指定的一个数据库行记灵对象
     *
     * @param string $row 数据库行记灵对象名
     * @return Row | mixed
     * @throws \Exception
     */
    public static function getRow($row)
    {
        $class = 'Cache\\Row\\' . $row;
        if (class_exists($class)) return (new $class());

        $path = self::$runtime->getPathCache() . '/Row/' . $row . '.php';
        //if (!file_exists($path)) {
        $serviceSystem = Be::getService('Cache');
        $serviceSystem->updateRow($row);
        //}

        include_once $path;

        if (!class_exists($class)) {
            throw new \Exception('行记灵对象 ' . $row . ' 不存在！');
        }

        return (new $class());
    }

    /**
     * 获取指定的一个数据库表对象
     *
     * @param string $table 表名
     * @return Table
     * @throws \Exception
     */
    public static function getTable($table)
    {
        $class = 'Cache\\Table\\' . $table;
        if (class_exists($class)) return (new $class());

        $path = self::$runtime->getPathCache() . '/Table/' . $table . '.php';
        //if (!file_exists($path)) {
        $serviceCache = Be::getService('Cache');
        $serviceCache->updateTable($table);
        //}

        include_once $path;

        if (!class_exists($class)) {
            throw new \Exception('表对象 ' . $table . ' 不存在！');
        }

        return (new $class());
    }

    /**
     * 获取指定的一个数据库表对象
     *
     * @param string $table 表名
     * @return TableConfig
     * @throws \Exception
     */
    public static function getTableConfig($table)
    {
        $class = 'Data\\TableConfig\\' . $table;
        if (class_exists($class)) return (new $class());

        $path = self::$runtime->getPathData() . '/TableConfig/' . $table . '.php';
        if (file_exists($path)) {
            include_once $path;

            if (!class_exists($class)) {
                throw new \Exception('表对象 ' . $table . ' 不存在！');
            }

            return (new $class());
        } else {
            return (new TableConfig());
        }
    }


    /**
     * 获取指定的一个模板
     *
     * @param string $template 模板名
     * @return Template
     * @throws \Exception
     */
    public static function getTemplate($template)
    {
        $theme = self::$runtime->getTheme();

        $class = 'Cache\\Template\\' . $theme . '\\'  . str_replace('.', '\\', $template);
        if (isset(self::$cache[$class])) return self::$cache[$class];

        $path = self::$runtime->getPathCache() . '/Template/' .  $theme . '/' . str_replace('.', '/', $template) . '.php';
        if (!file_exists($path)) {
            $serviceSystem = Be::getService('Cache');
            $serviceSystem->updateTemplate($template, $theme);
        }

        include_once $path;

        if (!class_exists($class)) throw new \Exception('模板（' . $template . '）不存在！');

        self::$cache[$class] = new $class();
        return self::$cache[$class];
    }


    public static function getRuntime()
    {
        if (self::$runtime == null) {
            self::$runtime = new Runtime();
        }
        return self::$runtime;
    }
}
