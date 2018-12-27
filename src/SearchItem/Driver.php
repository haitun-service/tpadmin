<?php

namespace Haitun\Service\TpAdmin\SearchItem;

use Haitun\Service\TpAdmin\System\Be;
use Haitun\Service\TpAdmin\Util\Str;

/**
 * 驱动
 */
abstract class Driver
{

    protected $key = ''; // 键名
    protected $name = ''; // 中文名称
    protected $uiType = 'text'; // UI界面类型
    protected $keyValueType = 'string'; // 可选值键值对类型
    protected $keyValues = null; // 可选值键值对

    /**
     * 构造函数
     *
     * @param string $key 键名
     * @param array $params 注解参数
     */
    public function __construct($key, $params = array())
    {
        $this->key = $key;

        if (isset($params['name'])) {
            $this->name = $params['name'];
        }

        if (isset($params['uiType'])) {
            $this->uiType = $params['uiType'];
        }

        if (isset($params['keyValueType'])) {
            $this->keyValueType = $params['keyValueType'];
        }

        switch ($this->keyValueType) {
            case 'sql':
                if (isset($params['keyValues'])) {
                    $keyValues = trim($params['keyValues']);
                    if ($keyValues) {

                        $keyValues = explode('|', $keyValues);
                        $sql = $keyValues[0];
                        $cache = 0;
                        if (count($keyValues) > 1) {
                            $cache = intval($keyValues[1]);
                        }

                        if ($cache > 0) {
                            $keyValues = Be::getDb()->withCache($cache)->getKeyValues($sql);
                        } else {
                            $keyValues = Be::getDb()->getKeyValues($sql);
                        }

                        $this->keyValues = $keyValues;
                    }
                }
                break;
            case 'eval':
                if (isset($params['keyValues'])) {
                    $keyValues = trim($params['keyValues']);
                    if ($keyValues) {

                        $newKeyValues = null;
                        try {
                            $newKeyValues = eval($keyValues);
                        } catch (\Throwable $e) {

                        }

                        if (is_array($newKeyValues)) {
                            $this->keyValues = $newKeyValues;
                        }
                    }
                }
                break;
            default:
                if (isset($params['keyValues'])) {
                    $keyValues = trim($params['keyValues']);
                    if ($keyValues) {
                        $keyValues = $this->parseKeyValues($keyValues);
                        $this->keyValues = $keyValues;
                    }
                }
        }

    }


    /**
     * 编辑
     *
     * @return bool
     */
    public function getHtml()
    {
        $html = '<div class="input-group">';
        $html .= '<label class="input-group-addon bold">'.$this->name.'</label>';

        $class = get_called_class();
        if (strpos($class, '\\') !== false) {
            $class = substr(strrchr($class, '\\'), 1);
        }
        $class = Str::camel2Hyphen($class);

        if ($this->keyValues !== null && is_array($this->keyValues)) {

            switch ($this->uiType) {

                case 'radio':
                    $i = 0;
                    foreach ($this->keyValues as $key => $value) {
                        $html .= '<input type="radio" name="' . $this->key . '" id="' . $this->key . '-' . $i . '" value="' . $key . '"  class="' . $class . '"' . '>';
                        $html .= '<label for="' . $this->key . '-' . $i . '">';
                        $html .= $value;
                        $html .= '</label>';
                        $i++;
                    }
                    break;

                case 'checkbox':
                    $i = 0;
                    foreach ($this->keyValues as $key => $value) {
                        $html .= '<input type="checkbox" name="' . $this->key . '" id="' . $this->key . '-' . $i . '" value="' . $key . '" class="' . $class . '"' . '>';
                        $html .= '<label for="' . $this->key . '-' . $i . '">';
                        $html .= $value;
                        $html .= '</label>';
                        $i++;
                    }
                    break;

                case 'select':
                    $html .= '<select name="' . $this->key . '" id="' . $this->key . '"  class="' . $class . '" />';
                    foreach ($this->keyValues as $key => $value) {
                        $html .= '<option value="' . $key . '"' . '>' . $value . '</option>';
                    }
                    $html .= '</select>';
                    break;
                default:
                    $html .= '<input type="text" name="' . $this->key . '" id="' . $this->key . '" class="'.$class.'" />';

            }

        } else {
            switch ($this->uiType) {
                case 'text':
                case 'number':
                case 'range':
                case 'date':
                case 'datetime':
                    $html .= '<input type="' . $this->uiType . '" name="' . $this->key . '" id="' . $this->key . '" class="'.$class.'" />';
                    break;
                case 'textarea':
                    $html .= '<textarea name="' . $this->key . '" id="' . $this->key . '" class="'.$class.'" ></textarea>';
                    break;
                default:
                    $html .= '<input type="text" name="' . $this->key . '" id="' . $this->key . '" class="'.$class.'" />';
            }
        }

        $html .= '</div>';
        return $html;
    }


    /**
     * 格式化以竖线，冒号分隔的KV键值对
     * @param string $keyValues 1:允许|0:不允许
     * @return array
     */
    public function parseKeyValues($keyValues)
    {
        $keyValues = explode('|', $keyValues);
        $formattedKeyValues = [];
        foreach ($keyValues as $keyValue) {
            $pos = strpos($keyValue, ':');
            if ($pos === false) {
                $key = $keyValue;
                $val = $keyValue;
            } else {
                $key = substr($keyValue, 0, $pos);
                $val = substr($keyValue, $pos + 1);
            }

            $formattedKeyValues[$key] = $val;
        }

        return $formattedKeyValues;
    }


    public function buildWhere($condition) {
        if (isset($condition[$this->key])) {
            return '`' . $this->key . '`=\''.$condition[$this->key].'\'';
        }
        return '';
    }

}
