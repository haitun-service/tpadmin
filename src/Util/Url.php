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
                $encodedUrl = U(CONTROLLER_NAME.'/'.$action, $params);
                break;

            case 'tp5':
                $module = \Think\Request::instance()->module();
                $controller = \Think\Request::instance()->controller();
                $encodedUrl = url('/'.$module.'/'.$controller.'/'.$action, $params);
                break;

        }

        return $encodedUrl;
    }

}
