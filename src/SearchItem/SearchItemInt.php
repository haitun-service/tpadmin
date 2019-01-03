<?php

namespace Haitun\Service\TpAdmin\SearchItem;


/**
 * 搜索项 整型
 */
class SearchItemInt extends Driver
{
    protected $min = null;
    protected $max = null;
    protected $step = 1;

    /**
     * 构造函数
     *
     * @param string $key 键名
     * @param array $params 注解参数
     */
    public function __construct($key, $params = array())
    {
        parent::__construct($key, $params);

        if (isset($params['min']) && is_numeric($params['min'])) {
            $this->min = intval($params['min']);
        }

        if (isset($params['max']) && is_numeric($params['max'])) {
            $this->max = intval($params['max']);
        }

        if (isset($params['step']) && is_numeric($params['step'])) {
            $this->step = intval($params['step']);
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
                        $html .= '<input type="radio" name="' . $this->key . '" id="' . $this->key . '-' . $i . '" value="' . $key . '" ' . 'class="form-control search-item-int"';
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
                    $html .= '<select name="' . $this->key . '" id="' . $this->key . '" class="form-control search-item-int">';
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

            switch ($this->uiType) {
                case 'number':
                    $html .= '<input type="number"';

                    if ($this->min !== null) {
                        $html .= ' min="' . $this->min . '"';
                    }

                    if ($this->max !== null) {
                        $html .= ' max="' . $this->max . '"';
                    }

                    $html .= ' step="' . $this->step . '"';

                    break;
                case 'range':
                    $html .= '<input type="range"';

                    if ($this->min !== null) {
                        $html .= ' min="' . $this->min . '"';
                    }

                    if ($this->max !== null) {
                        $html .= ' max="' . $this->max . '"';
                    }

                    $html .= ' step="' . $this->step . '"';

                    break;
                default:
                    $html .= '<input type="text"';

                    if ($this->min !== null) {
                        $html .= ' data-min="' . $this->min . '"';
                    }

                    if ($this->max !== null) {
                        $html .= ' data-max="' . $this->max . '"';
                    }

                    $html .= ' data-step="' . $this->step . '"';

                    break;
            }

            $html .= ' name="' . $this->key . '" id="' . $this->key . '"';
            $html .= ' class="form-control search-item-int"';
            if ($this->defaultValue !== null) {
                $html .= ' value="' . $this->defaultValue . '"';
            }
            $html .= ' />';
        }

        $html .= '</div>';
        return $html;
    }




}
