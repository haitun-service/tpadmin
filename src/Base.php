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

        $supportMime = [
            'html' => 'text/html',
            'htm' => 'text/html',
            'xhtml' => 'application/xhtml+xml',
            'xml' => 'text/xml',
            'txt' => 'text/plain',
            'log' => 'text/plain',

            'js' => 'application/javascript',
            'json' => 'application/json',
            'css' => 'text/css',

            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'bmp' => 'image/bmp',
            'ico' => 'image/icon',
            'svg' => 'image/svg+xml',

            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',

            'mp4' => 'video/avi',
            'avi' => 'video/avi',
            '3gp' => 'application/octet-stream',
            'flv' => 'application/octet-stream',
            'swf' => 'application/x-shockwave-flash',

            'zip' => 'application/zip',
            'rar' => 'application/octet-stream',

            'ttf' => 'application/octet-stream',
            'fon' => 'application/octet-stream',

            'doc' => 'application/msword',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'mdb' => 'application/msaccess',
            'chm' => 'application/octet-stream',

            'pdf' => 'application/pdf',
        ];

        $pathInfo = pathinfo($src);

        $ext = strtolower($pathInfo['extension']);
        if (isset($supportMime[$ext])) {
            header('Content-Type: ' . $supportMime[$ext]);
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
            $content = file_get_contents($path);
            if ($ext == 'css') {
                $pattern = '/\s*url\s*\(\s*(?:\'|\")?([^\'\)]+)(?:\'|\")?\s*\)/';
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] as $m) {
                        $replaceFrom = $m;
                        $replaceTo1 = $pathInfo['dirname'];

                        $replaceTo2 = $m;
                        $post = strpos($replaceTo2, '?');
                        if ($post !== false) {
                            $replaceTo2 = substr($replaceTo2, 0, $post);
                        }

                        while (substr($replaceTo2, 0, 3) == '../') {
                            $replaceTo2 = substr($replaceTo2, 3);
                            $replaceTo1 = substr($replaceTo1, 0 , strrpos($replaceTo1, '/'));
                        }
                        $replaceTo = $replaceTo1 . '/' . $replaceTo2;
                        $replaceTo = \Haitun\Service\TpAdmin\Util\Url::encode('assets', array('src' => $replaceTo));

                        $content = str_replace($replaceFrom, $replaceTo, $content);
                    }
                }
            }

            echo $content;
        }
        exit;
    }

}
