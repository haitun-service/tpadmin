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
    protected $keyValues = null; // 可选值键值对
    protected $autoComplete = null; // 自动完成
    protected $defaultValue = null; // 默认值
    protected $table = null; // 表名、连表时的别名
    protected $readonly = false;
    protected $disabled = false;


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

        if (isset($params['keyValues'])) {
            if (is_array($params['keyValues'])) {
                $this->keyValues = $params['keyValues'];
            } elseif (is_object($params['keyValues'])) {
                $this->keyValues = get_object_vars($params['keyValues']);
            } elseif (is_string($params['keyValues']) || is_numeric($params['keyValues'])) {
                $keyValues = trim($params['keyValues']);
                if ($keyValues) {
                    $keyValues = $this->parseKeyValues($keyValues);
                    $this->keyValues = $keyValues;
                }
            }
        }

        if (isset($params['autoComplete'])) {
            $this->autoComplete = $params['autoComplete'];
        }

        if (isset($params['defaultValue'])) {
            $this->defaultValue = $params['defaultValue'];
        }

        if (isset($params['table'])) {
            $this->table = $params['table'];
        }

        if (isset($params['readonly'])) {
            $this->readonly = $params['readonly'];
        }

        if (isset($params['disabled'])) {
            $this->disabled = $params['disabled'];
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
        $html .= '<label class="input-group-addon bold">' . $this->name . '</label>';

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
                        $html .= '<input type="radio" name="' . $this->key . '" id="' . $this->key . '-' . $i . '" value="' . $key . '"  class="' . $class . '"';
                        if ($this->defaultValue !== null && $this->defaultValue == $key) {
                            $html .= ' checked';
                        }
                        if ($this->readonly) $html .= ' readonly';
                        if ($this->disabled) $html .= ' disabled';
                        $html .= ' />';
                        $html .= '<label for="' . $this->key . '-' . $i . '">';
                        $html .= $value;
                        $html .= '</label>';
                        $i++;
                    }
                    break;

                case 'checkbox':
                    $i = 0;
                    foreach ($this->keyValues as $key => $value) {
                        $html .= '<input type="checkbox" name="' . $this->key . '" id="' . $this->key . '-' . $i . '" value="' . $key . '" class="' . $class . '"';
                        if ($this->defaultValue !== null && $this->defaultValue == $key) {
                            $html .= ' checked';
                        }
                        if ($this->readonly) $html .= ' readonly';
                        if ($this->disabled) $html .= ' disabled';
                        $html .= ' />';
                        $html .= '<label for="' . $this->key . '-' . $i . '">';
                        $html .= $value;
                        $html .= '</label>';
                        $i++;
                    }
                    break;

                case 'select':
                    $html .= '<select name="' . $this->key . '" id="' . $this->key . '"  class="' . $class . '"';
                    if ($this->readonly) $html .= ' readonly';
                    if ($this->disabled) $html .= ' disabled';
                    $html .= '>';
                    $html .= '<option value="">不限</option>';
                    foreach ($this->keyValues as $key => $value) {
                        $html .= '<option value="' . $key . '"';
                        if ($this->defaultValue !== null && $this->defaultValue == $key) {
                            $html .= ' selected';
                        }
                        $html .= '>' . $value . '</option>';
                    }
                    $html .= '</select>';
                    break;
                default:
                    $html .= '<input type="text" name="' . $this->key . '" id="' . $this->key . '" class="' . $class . '"';
                    if ($this->defaultValue !== null) {
                        $html .= ' value="' . $this->defaultValue . '"';
                    }
                    if ($this->readonly) $html .= ' readonly';
                    if ($this->disabled) $html .= ' disabled';
                    $html .= ' />';

            }

        } else {
            switch ($this->uiType) {
                case 'text':
                case 'number':
                case 'range':
                case 'date':
                case 'datetime':
                    $html .= '<input type="' . $this->uiType . '" name="' . $this->key . '" id="' . $this->key . '" class="' . $class . '"';
                    if ($this->defaultValue !== null) {
                        $html .= ' value="' . $this->defaultValue . '"';
                    }
                if ($this->readonly) $html .= ' readonly';
                if ($this->disabled) $html .= ' disabled';
                $html .= ' />';
                    break;
                case 'textarea':
                    $html .= '<textarea name="' . $this->key . '" id="' . $this->key . '" class="' . $class . '"';
                    if ($this->readonly) $html .= ' readonly';
                    if ($this->disabled) $html .= ' disabled';
                    $html .= '>';
                    if ($this->defaultValue !== null) {
                        $html .= $this->defaultValue;
                    }
                    $html .= '</textarea>';
                    break;
                default:
                    $html .= '<input type="text" name="' . $this->key . '" id="' . $this->key . '" class="' . $class . '"';
                    if ($this->defaultValue !== null) {
                        $html .= ' value="' . $this->defaultValue . '"';
                    }
                    if ($this->readonly) $html .= ' readonly';
                    if ($this->disabled) $html .= ' disabled';
                    $html .= ' />';
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


    public function buildWhere($condition)
    {
        if (isset($condition[$this->key]) && $condition[$this->key]) {
            return ($this->table === null ? '' : ('`' . $this->table . '`.')) . '`' . $this->key . '`=\'' . $condition[$this->key] . '\'';
        }
        return '';
    }

}
