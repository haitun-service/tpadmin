<?php

namespace Haitun\Service\TpAdmin\SearchItem;


/**
 * 搜索项 整型
 */
class SearchItemDateRange extends Driver
{

    protected $defaultStartDate = null;
    protected $defaultEndDate = null;

    /**
     * 构造函数
     *
     * @param string $key 键名
     * @param array $params 注解参数
     */
    public function __construct($key,  $params = array())
    {
        parent::__construct($key, $params);

        if (isset($params['defaultStartDate'])) {
            $this->defaultStartDate = $params['defaultStartDate'];
        }

        if (isset($params['defaultEndDate'])) {
            $this->defaultEndDate = $params['defaultEndDate'];
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
        $html .= '<input class="form-control" type="text" id="'.$this->key.'_start_date" name="'.$this->key.'_start_date"';
        if ($this->defaultStartDate !== null) {
            $html .= ' value="' . $this->defaultStartDate . '"';
        }
        $html .= ' placeholder="开始日期" readonly />';
        $html .= '<span class="input-group-addon">~</span>';

        $html .= '<input class="form-control" type="text" id="'.$this->key.'_end_date" name="'.$this->key.'_end_date"';
        if ($this->defaultEndDate !== null) {
            $html .= ' value="' . $this->defaultEndDate . '"';
        }
        $html .= ' placeholder="结束日期" readonly />';
        $html .= '</div>';

        $html .= '<script>';
        $html .= '$(document).ready(function(){';
        $html .= 'laydate.render({elem: \'#'.$this->key.'_start_date\'});';
        $html .= 'laydate.render({elem: \'#'.$this->key.'_end_date\'});';
        $html .= '})';
        $html .= '</script>';

        return $html;
    }



    public function buildWhere($condition) {
        $where = '';

        $startDate = null;
        if (isset($condition[$this->key.'_start_date']) && $condition[$this->key.'_start_date']) {
            $startDate = $condition[$this->key.'_start_date'];
            $where = ' ' . ($this->table === null ? '' : ('`' . $this->table . '`.')) . '`' . $this->key . '`>=\'' . $startDate .' 00:00:00\'';
        }

        $endDate = null;
        if (isset($condition[$this->key.'_end_date']) && $condition[$this->key.'_end_date']) {
            $endDate = $condition[$this->key.'_end_date'];
            if ($where) $where .= ' AND';
            $where .= ' ' . ($this->table === null ? '' : ('`' . $this->table . '`.')) . '`' . $this->key . '`<=\'' . $endDate .' 23:59:59\'';
        }

        return $where;
    }

}
