<?php
namespace Haitun\Service\TpAdmin;

use Haitun\Service\TpAdmin\System\Be;
use Haitun\Service\TpAdmin\System\Cache;
use Haitun\Service\TpAdmin\System\Cookie;
use Haitun\Service\TpAdmin\System\Request;
use Haitun\Service\TpAdmin\System\Response;

/**
 * Trait SqlReport Sql 查询报表
 * @package Haitun\Service\TpAdmin 海豚服务 管理框架
 */
trait SqlReport
{
    use Base;

    protected $config = array(

        'name' => '拣货任务订单明细报表',

        'sql' => array(
            'count' => 'SELECT COUNT(*) FROM ws_pick_task',
            'data' => 'SELECT * FROM ws_pick_task',
            'orderBy' => 'id', // 默认排序字段
            'orderByDir' => 'DESC' // 默认排序方式
        ),

        'search' => array(
            'warehouse_id' => array(
                'name' => '仓库',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemInt',
                'cols' => 3
            ),
            'task_type' => array(
                'name' => '任务类型',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemInt',
                'keyValues' => array(
                    1 => '自动',
                    2 => '手动'
                ),
                'cols' => 3
            ),
            'task_status' => array(
                'name' => '任务状态',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemInt',
                'keyValues' => array(
                    1 => '自动',
                    2 => '手动'
                ),
                'cols' => 3
            ),
            'create_time' => array(
                'name' => '任务创建时间',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemDateRange',
                'cols' => 3
            ),



            'task_sn' => array(
                'name' => '任务编号',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemString',
                'cols' => 3
            ),
            'box_sn' => array(
                'name' => '订单编号',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemString',
                'cols' => 3
            ),
            'sku' => array(
                'name' => 'SKU',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemString',
                'cols' => 3
            ),
            'pick_time' => array(
                'name' => '任务拣货时间',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemDateRange',
                'cols' => 3
            ),



            'create_by' => array(
                'name' => '创建人',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemString',
                'cols' => 3
            ),
            'receive_by' => array(
                'name' => '拣货人',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemString',
                'cols' => 3
            ),
            'complete_by' => array(
                'name' => '核对人',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemString',
                'cols' => 3
            ),
            'complete_time' => array(
                'name' => '任务完成时间',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemDateRange',
                'cols' => 3
            ),

        ),




        'fields' => array(
            'task_sn' => array(
                'name' => '任务编号'
            ),
            'box_sn' => array(
                'name' => '订单编号'
            ),
            'task_type' => array(
                'name' => '任务类型',
                'keyValues' => array(
                    1 => '自动',
                    2 => '手动'
                ),
            ),
            'task_status' => array(
                'name' => '任务状态'
            ),
            'sku' => array(
                'name' => 'SKU'
            ),
            'sku_area' => array(
                'name' => '储位'
            ),
            'id' => array(
                'name' => '所需数量'
            ),
            'id' => array(
                'name' => '拣货数量'
            ),
            'id' => array(
                'name' => '拣货状态'
            ),
            'id' => array(
                'name' => '打印状态'
            ),
        ),
    );

    
    /**
     * 列表展示
     */
    public function lists()
    {
        Response::set('config', $this->config);

        if (Request::isPost()) {

            $offset = Request::post('offset', 0);
            $limit = Request::post('limit', 0, 'int');
            if ($limit < 0) $limit = 0;
            if (!$limit) {
                $cookieLimit = Cookie::get(get_called_class() . ':limit', 0, 'int');
                if ($cookieLimit) {
                    $limit = $cookieLimit;
                } else {
                    $limit = 12;
                }
            } else {
                Cookie::set(get_called_class() . ':limit', $limit, 86400 * 30);
            }

            $cache = false;
            if (isset($this->config['cache'])) {
                $cache = $this->config['cache'];
            }

            $where = $this->buildWhere(Request::post(null, null, ''));

            $sql = $this->config['sql']['count'];
            if ($where) {
                $sql .= $where;
            }

            $db = Be::getDb();

            $total = null;
            if ($cache) {
                $cacheKey = 'SqlReport:' . $sql;
                $cacheValue = Cache::get($cacheKey);
                if ($cacheValue) {
                    $total = $cacheValue;
                } else {
                    $total = $db->getValue($sql);
                    Cache::set($cacheKey, $total, 600);
                }
            } else {
                $total = $db->getValue($sql);
            }

            $sql = $this->config['sql']['data'];
            if ($where) {
                $sql .= $where;
            }

            if (isset($this->config['sql']['orderBy'])) {
                $orderBy = $this->config['sql']['orderBy'];
                $orderByDir = isset($this->config['sql']['orderByDir']) ? $this->config['sql']['orderByDir'] : 'DESC';
                $sql .= ' ORDER BY ' . $orderBy . ' ' . $orderByDir;
            }

            $sql .= ' LIMIT ' . $offset . ', ' . $limit;

            $rows = null;
            if ($cache) {
                $cacheKey = 'SqlReport:' . $sql;
                $cacheValue = Cache::get($cacheKey);
                if ($cacheValue) {
                    $rows = $cacheValue;
                } else {
                    $rows = $db->getObjects($sql);
                    Cache::set($cacheKey, $rows, 600);
                }
            } else {
                $rows = $db->getObjects($sql);
            }

            foreach ($rows as &$row) {
                foreach ($this->config['fields'] as $key => $field) {

                    if (!isset($row->$key)) {
                        $row->$key = '-';
                        continue;
                    }

                    if (isset($field['keyValues'])) {
                        if (isset($field['keyValues'][$key])) {
                            $row->$key = $field['keyValues'][$key];
                        } else {
                            $row->$key = '-';
                        }
                    }
                }
            }

            Response::set('total', $total);
            Response::set('rows', $rows);
            Response::ajax();
        }

        Response::setTitle($this->config['name']);
        Response::display('SqlReprot.lists');
    }

    /*
     * 导出
     */
    public function export()
    {
        set_time_limit(3600);

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename=' . date('YmdHis') . '.csv');
        $handler = fopen('php://output', 'w') or die("can't open php://output");
        fwrite($handler, pack('H*', 'EFBBBF')); // 写入 BOM 头

        $headers = array();
        foreach ($this->config['fields'] as $key => $field) {
            $headers[] = $field['name'];
        }
        fputcsv($handler, $headers);

        $where = $this->buildWhere(Request::post(null, null, ''));

        $sql = $this->config['sql']['data'];
        if ($where) {
            $sql .= $where;
        }

        $db = Be::getDb();
        $rows = $db->getYieldObjects($sql);
        foreach ($rows as $row) {
            $values = array();
            foreach ($this->config['fields'] as $key => $field) {
                if (!isset($row->$key)) {
                    $values[] = '-';
                    continue;
                }

                if (isset($field['keyValues'])) {
                    if (isset($field['keyValues'][$key])) {
                        $values[] = $field['keyValues'][$key];
                    } else {
                        $values[] = '-';
                    }
                } else {
                    $values[] = $row->$key;
                }
            }

            fputcsv($handler, $values);
        }

        fclose($handler) or die("can't close php://output");
    }



    /**
     * @param $condition
     * @return string
     */
    protected function buildWhere($condition)
    {
        $wheres = array();
        foreach($this->config['search'] as $key => $search) {
            $driver = $search['driver'];
            $searchDriver = new $driver($key, $search);
            $where = $searchDriver->buildWhere($condition);
            if ($where) $wheres[] = $where;
        }

        if (count($wheres)) {
            return ' WHERE '. implode(' AND ', $wheres);
        }

        return '';
    }


}
