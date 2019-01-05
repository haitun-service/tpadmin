<?php
namespace Haitun\Service\TpAdmin\System;

/**
 *  运行时
 * @package System
 *
 */
class Runtime
{

    private $pathRoot = null;

    private $urlRoot = null;

    private $dirData = 'data';

    private $dirCache = 'cache';

    private $framework = null;

    /**
     * 数据库配置文件
     *
     * @var array
     */
    private $dbConfig = null;


    /**
     * 缓存配置文件
     *
     * @var array
     */
    private $cacheConfig = array(
        'driver' => 'File'
    );

    /**
     * 主题
     *
     * @var string
     */
    private $theme = 'hplus';


    public function __construct()
    {
    }


    /**
     * 设置BE框架的根路径
     *
     * @param string $pathRoot BE框架的根路径，绝对路径
     * @return Runtime
     */
    public function setPathRoot($pathRoot)
    {
        $this->pathRoot = $pathRoot;
        return $this;
    }

    /**
     * 获取BE框架的根路径
     *
     * @return string
     */
    public function getPathRoot()
    {
        return $this->pathRoot;
    }

    /**
     * @return string
     */
    public function getPathCache()
    {
        return $this->pathRoot.'/'.$this->dirCache;
    }

    /**
     * @return string
     */
    public function getPathData()
    {
        return $this->pathRoot.'/'.$this->dirData;
    }

    /**
     * @return string
     */
    public function getUrlRoot()
    {
        if ($this->urlRoot === null) {
            $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
            $url .= isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']));
            if (defined('ENV')) {
                if (ENV == 'local') {
                    if (strpos($_SERVER['PHP_SELF'], '/index.php' !== false)) {
                        $url .= substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/index.php'));
                    } elseif (strpos($_SERVER['PHP_SELF'], '/index.local.php' !== false)) {
                        $url .= substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/index.local.php'));
                    }
                } else {
                    $url .= substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/index' . '.' . ENV . '.php'));
                }
            }
            $this->urlRoot = $url;
        }

        return $this->urlRoot;
    }

    /**
     * @return string
     */
    public function getUrlData()
    {
        return $this->getUrlRoot().'/'.$this->dirData;
    }

    /**
     * @param string $dirData
     * @return Runtime
     */
    public function setDirData($dirData)
    {
        $this->dirData = $dirData;
        return $this;
    }

    /**
     * @return string
     */
    public function getDirData()
    {
        return $this->dirData;
    }

    /**
     * @return string
     */
    public function getDirCache()
    {
        return $this->dirCache;
    }

    /**
     * @param string $dirCache
     * @return Runtime
     */
    public function setDirCache($dirCache)
    {
        $this->dirCache = $dirCache;
        return $this;
    }

    /**
     * 获取数据库参数设置
     *
     * @return array
     */
    public function getDbConfig()
    {
        return $this->dbConfig;
    }

    /**
     * 设置数据库参数
     *
     * @param array $dbConfig
     * @return Runtime
     */
    public function setDbConfig($dbConfig)
    {
        $this->dbConfig = $dbConfig;
        return $this;
    }

    /**
     * 获取缓存参数设置
     *
     * @return array
     */
    public function getCacheConfig()
    {
        return $this->cacheConfig;
    }

    /**
     * 设置缓存参数设置
     *
     * @param array $cacheConfig
     * @return Runtime
     */
    public function setCacheConfig($cacheConfig)
    {
        $this->cacheConfig = $cacheConfig;
        return $this;
    }

    /**
     * 获取当前主题
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * 设置主题
     *
     * @param string $theme
     * @return Runtime
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * 获取当前框架
     *
     * @return string
     */
    public function getFramework()
    {
        return $this->framework;
    }

    /**
     * 设置框架
     *
     * @param string $framework
     * @return Runtime
     */
    public function setFramework($framework)
    {
        $this->framework = $framework;
        return $this;
    }

}
