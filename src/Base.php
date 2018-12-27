<?php
namespace Haitun\Service\TpAdmin;

use Haitun\Service\TpAdmin\System\Be;
use Haitun\Service\TpAdmin\System\Cookie;
use Haitun\Service\TpAdmin\System\Request;
use Haitun\Service\TpAdmin\System\Response;

/**
 * Trait Base
 * @package Haitun\Service\TpAdmin 海豚服务 管理框架
 */
trait Base
{


    public function assets() {
        $src = Request::get('src', '', 'null');
        $src = str_replace('../', '', $src);

        $ext = strtolower(strrchr($src, '.'));
        if ($ext == '.js') {
            header('Content-type: text/javascript');
        } elseif ($ext == '.css') {
            header('Content-type: text/css');
        } else {
            exit;
        }

        $cache_time = 86400;

        //发送Last-Modified头标，设置文档的最后的更新日期。
        header ("Last-Modified: " .gmdate("D, d M Y H:i:s", time() )." GMT");

        //发送Expires头标，设置当前缓存的文档过期时间，GMT格式，我们使用的是GMT+8时区
        header ("Expires: " .gmdate("D, d M Y H:i:s", time()+$cache_time )." GMT");

        //发送Cache_Control头标，设置xx秒以后文档过时,可以代替Expires，如果同时出现，max-age优先。
        header ("Cache-Control: max-age=$cache_time");

        $path = Be::getRuntime()->getPathRoot() . '/vendor/haitun-service/tpadmin/src'.$src;
        if (file_exists($path)) {
            echo file_get_contents($path);
        }
        exit;
    }

}
