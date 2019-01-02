<?php

namespace Haitun\Service\TpAdmin\SearchItem;


/**
 * 搜索项 布尔值
 */
class SearchItemBool extends Driver
{

    /**
     * 构造函数
     *
     * @param string $key 键名
     * @param array $params 注解参数
     */
    public function __construct($key, $params = array())
    {
        parent::__construct($key, $params);
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

        if ($this->keyValues !== null && is_array($this->keyValues)) {

            switch ($this->uiType) {

                case 'radio':
                    $i = 0;
                    foreach ($this->keyValues as $key => $value) {
                        $html .= '<input type="radio" name="' . $this->key . '" id="' . $this->key . '-' . $i . '" value="' . $key . '" '. 'class="form-control search-item-bool"';
                        if ($this->defaultValue !== null && $this->defaultValue == $key) {
                            $html .= ' checked';
                        }
                        $html .= ' />';
                        $html .= '<label for="' . $this->key . '-' . $i . '">';
                        $html .= $value;
                        $html .= '</label>';
                        $i++;
                    }
                    break;

                case 'select':
                    $html .= '<select name="' . $this->key . '" id="' . $this->key . '" class="form-control search-item-bool">';
                    foreach ($this->keyValues as $key => $value) {
                        $html .= '<option value="' . $key . '"';
                        if ($this->defaultValue !== null && $this->defaultValue == $key) {
                            $html .= ' selected';
                        }
                        $html .= '>' . $value . '</option>';
                    }
                    $html .= '</select>';
                    break;

            }

        } else {

            switch ($this->uiType) {

                case 'checkbox':
                    $html .= '<input type="checkbox" name="' . $this->key . '" id="' . $this->key . '" value="1" ' . 'class="form-control search-item-bool"';
                    if ($this->defaultValue !== null && $this->defaultValue) {
                        $html .= ' checked';
                    }
                    $html .= ' /> 是';
                    break;

                case 'select':
                    $html .= '<select name="' . $this->key . '" id="' . $this->key . '"  class="form-control search-item-bool">';
                    $html .= '<option value="1"';
                    if ($this->defaultValue !== null && $this->defaultValue) {
                        $html .= ' selected';
                    }
                    $html .= '>是</option>';
                    $html .= '<option value="0"';
                    if ($this->defaultValue !== null && !$this->defaultValue) {
                        $html .= ' selected';
                    }
                    $html .= '>否</option>';
                    $html .= '</select>';
                    break;

                case 'radio':
                default:
                    $html .= '<input type="radio" name="' . $this->key . '" id="' . $this->key . '-0" value="1" class="form-control search-item-bool"';
                    if ($this->defaultValue !== null && $this->defaultValue) {
                        $html .= ' checked';
                    }
                    $html .= ' />';
                    $html .= '<label for="' . $this->key . '-0">是</label>';

                    $html .= '<input type="radio" name="' . $this->key . '" id="' . $this->key . '-1" value="0" class="form-control search-item-bool"';
                    if ($this->defaultValue !== null && !$this->defaultValue) {
                        $html .= ' checked';
                    }
                    $html .= ' />';
                    $html .= '<label for="' . $this->key . '-1">否</label>';

            }
        }

        $html .= '</div>';
        return $html;
    }




}
