<?php
namespace Haitun\Service\TpAdmin\System;

/**
 * Response
 * @package System
 *
 * @method void setTitle(string $title) static 设置 title
 * @method void setMetaKeywords(string $metaKeywords)  static 设置 meta keywords
 * @method void setMetaDescription(string $metaDescription)  static 设置 meta description
 */
class Response
{
    private static $data = array(); // 暂存数据


    /**
     * 向客户机添加一个字符串值属性的响应头信息
     */
    public static function addHeader($name, $value)
    {
    }

    /**
     * 向客户机设置一个字符串值属性的响应头信息，已存在时覆盖
     */
    public static function setHeader($name, $value)
    {
    }

    /**
     * 判断是否含响应头信息
     */
    public static function hasHeader($name)
    {
    }


    /**
     * 设置响应码，比如：200,304,404等
     */
    public static function setStatus($status)
    {
    }

    /**
     * 设置设置响应头content-type的内容
     */
    public static function setContentType($contentType)
    {
        header('Content-type: ' . $contentType);
    }

    /**
     * 请求重定向
     *
     * @param string $url 跳转网址
     */
    public static function redirect($url)
    {
        header('location:' . $url);
        exit();
    }

    /**
     * 设置暂存数据
     * @param string $name 名称
     * @param mixed $value 值 (可以是数组或对象)
     */
    public static function set($name, $value)
    {
        self::$data[$name] = $value;
    }

    /**
     * 设置暂存数据
     * @param mixed $data 数据(可以是数组或对象)
     */
    public static function setData($data)
    {
        self::$data = $data;
    }

    /**
     * 获取暂存数据
     *
     * @param string $name 名称
     * @return mixed
     */
    public static function get($name, $default = null)
    {
        if (isset(self::$data[$name])) return self::$data[$name];
        return $default;
    }

    /**
     * 以 JSON 输出暂存数据
     */
    public static function ajax()
    {
        header('Content-type: application/json');
        echo json_encode(self::$data);
        exit();
    }

    /**
     * 成功
     *
     * @param string $message 消息
     * @param string $redirectUrl 跳转网址
     * @param int $code 错误码
     */
    public static function success($message, $redirectUrl = 'referer', $code = 0)
    {
        self::set('success', true);
        self::set('message', $message);
        self::set('code', $code);
        if ($redirectUrl !== null) {
            if ($redirectUrl == 'referer') $redirectUrl = $_SERVER['HTTP_REFERER'];
            self::set('redirectUrl', $redirectUrl);
        }

        if (Request::isAjax()) {
            self::ajax();
        } else {
            self::display('success');
        }
    }

    /**
     * 失败
     *
     * @param string $message 消息
     * @param string $redirectUrl 跳转网址
     * @param int $code 错误码
     */
    public static function error($message, $redirectUrl = 'referer', $code = 1)
    {
        self::set('success', false);
        self::set('message', $message);
        self::set('code', $code);
        if ($redirectUrl !== null) {
            if ($redirectUrl == 'referer') $redirectUrl = $_SERVER['HTTP_REFERER'];
            self::set('redirectUrl', $redirectUrl);
        }

        if (Request::isAjax()) {
            self::ajax();
        } else {
            self::display('error');
        }
    }

    /**
     * 显示模板
     *
     * @param string $template 模板名
     */
    public static function display($template = null)
    {
        $templateInstance = Be::getTemplate($template);

        foreach (self::$data as $key => $val) {
            $templateInstance->$key = $val;
        }

        $templateInstance->display();
    }

    /**
     * 获取模板内容
     *
     * @param string $template 模板名
     * @return  string
     */
    public static function fetch($template)
    {
        ob_start();
        ob_clean();
        self::display($template);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /**
     * 结束输出
     *
     * @param string $string 输出内空
     * @return  string
     */
    public static function end($string = null)
    {
        if ($string === null) {
            exit;
        } else {
            exit('<!DOCTYPE html><html><head><meta charset="utf-8" /></head><body><div style="padding:10px;text-align:center;">' . $string . '</div></body></html>');
        }
    }

    /*
     * 封装 setXxx 方法
     */
    public static function __callStatic($fn, $args)
    {
        if (substr($fn, 0, 3) == 'set' && count($args) == 1) {
            self::$data[lcfirst(substr($fn, 3))] = $args[0];
        }
    }

}

