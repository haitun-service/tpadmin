<?php
use Haitun\Service\TpAdmin\System\Be;

/**
 * 处理网址
 * 
 * @param string $url 要处理的网址，启用 SEF 时生成伪静态页， 为空时返回网站网址
 * @return string
 */
function url($url)
{
    $urlRoot = Be::getRuntime()->getUrlRoot();

    $configSystem = Be::getConfig('System.System');
    if ($configSystem->sef) {
        $urls = explode('&', $url);

        if (count($urls) == 0) return $urlRoot;

        $app = null;
        $controller = null;
        $task = null;
        $params = array();

        foreach ($urls as $x) {
            $pos = strpos($x, '=');

            if ($pos !== false) {
                $key = substr($x, 0, $pos);
                $val = substr($x, $pos+1);

                if ($key == 'app') {
                    $app = $val;
                } elseif ($key == 'controller') {
                    $controller = $val;
                } elseif ($key == 'task') {
                    $task = $val;
                } else {
                    $params[$key] = $val;
                }
            }
        }

        if ($app === null) return $urlRoot;
        if ($controller === null) return $urlRoot . '/' . $app . $configSystem->sefSuffix;
        if ($task == null) return $urlRoot . '/' . $controller . $configSystem->sefSuffix;

        $router = Be::getRouter($app, $controller);
        return $router->encodeUrl($app, $controller, $task, $params);
    }

    return $urlRoot . '/?' . $url;
}
