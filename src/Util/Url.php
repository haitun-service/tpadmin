<?php
namespace Haitun\Service\TpAdmin\Util;

class Url
{

    /**
     * 按不同的框架生成网址
     *
     * @param string $action
     * @param array $params 参数
     * @return bool
     */
    public static function encode($action, $params = array())
    {
        $framework = \Haitun\Service\TpAdmin\System\Be::getRuntime()->getFramework();

        $encodedUrl = null;
        switch ($framework) {
            case 'tp3':
                $encodedUrl = U(CONTROLLER_NAME.'/'.$action);
                break;

            case 'tp5':
                $module = \Think\Request::instance()->module();
                $controller = \Think\Request::instance()->controller();
                $encodedUrl = url('/'.$module.'/'.$controller.'/'.$action);
                break;
            case 'tp5.1':
                $module = Request()->module();
                $controller =  Request()->controller();
                $encodedUrl = url('/'.$module.'/'.$controller.'/'.$action);
                break;
        }

        if (count($params) > 0) {
            if (strpos($encodedUrl, '?') === false) {
                $encodedUrl .= '?';
            } else {
                $encodedUrl .= '&';
            }

            $encodedUrl .= http_build_query($params);
        }


        return $encodedUrl;
    }

}
