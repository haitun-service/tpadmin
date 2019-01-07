<?php

namespace Haitun\Service\TpAdmin\SearchItem;


/**
 * 搜索项 整型
 */
class SearchItemString extends Driver
{

    protected $minLength = null;
    protected $maxLength = null;

    /**
     * 构造函数
     *
     * @param string $key 键名
     * @param array $params 注解参数
     */
    public function __construct($key, $params = array())
    {
        parent::__construct($key, $params);

        if (isset($params['minLength']) && is_numeric($params['minLength'])) {
            $this->minLength = intval($params['minLength']);
        }

        if (isset($params['maxLength']) && is_numeric($params['maxLength'])) {
            $this->maxLength = intval($params['maxLength']);
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

        if ($this->keyValues !== null && is_array($this->keyValues)) {

            switch ($this->uiType) {

                case 'radio':
                    $i = 0;
                    foreach ($this->keyValues as $key => $value) {
                        $html .= '<input type="radio" name="' . $this->key . '" id="' . $this->key . '-' . $i . '" value="' . $key . '" ' . 'class="form-control search-item-string"';
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
                    $html = '<select name="' . $this->key . '" id="' . $this->key . '" class="form-control search-item-string"';
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

            }

        } else {
            $html .= '<input type="text"';

            if ($this->minLength !== null) {
                $html .= ' data-minLength="' . $this->minLength . '"';
            }

            if ($this->maxLength !== null) {
                $html .= ' data-maxLength="' . $this->maxLength . '"';
            }

            $html .= ' name="' . $this->key . '" id="' . $this->key . '"';
            $html .= ' class="form-control search-item-string"';
        }
        if ($this->defaultValue !== null) {
            $html .= ' value="' . $this->defaultValue . '"';
        }
        if ($this->readonly) $html .= ' readonly';
        if ($this->disabled) $html .= ' disabled';
        $html .= ' />';

        $html .= '</div>';
        return $html;
    }




}
