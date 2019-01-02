<?php

namespace Haitun\Service\TpAdmin;

use Haitun\Service\TpAdmin\System\Be;
use Haitun\Service\TpAdmin\System\Cache;
use Haitun\Service\TpAdmin\System\Cookie;
use Haitun\Service\TpAdmin\System\Request;
use Haitun\Service\TpAdmin\System\Response;

/**
 * Trait DailyAggsReport
 * @package Haitun\Service\TpAdmin 海豚服务 管理框架
 */
trait DailyAggsReport
{
    use Base;

    /*
     * 配置项
    */
    public $config = array();

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

            $sql = $this->config['aggsKey']['sql'];
            $where = $this->buildWhere(Request::post(null, null, ''));
            if ($where) {
                $hasWhere = false;
                $pos = strpos(strtoupper($sql), ' WHERE ');
                if ($pos !== false) {
                    $pos2 = strpos($sql, ')');
                    if ($pos === false) {
                        $hasWhere = true;
                    } else {
                        if ($pos > $pos2) {
                            $hasWhere = true;
                        }
                    }
                }

                $sql .= $hasWhere ? ' AND ' : ' WHERE ';
                $sql .= $where;
            }

            if (isset($this->config['aggsKey']['orderBy'])) {
                $sql .= ' ' . $this->config['aggsKey']['orderBy'];
            } else {
                $sql .= ' ORDER BY aggs_date DESC';
            }

            $cache = false;
            if (isset($this->config['cache'])) {
                $cache = $this->config['cache'];
            }

            $db = Be::getDb();
            $total = null;
            $tmpSql = 'SELECT COUNT(*) FROM (' . $sql . ') t';

            if ($cache) {
                $cacheKey = 'DailyAggsReport:' . $tmpSql;
                $cacheValue = Cache::get($cacheKey);
                if ($cacheValue) {
                    $total = $cacheValue;
                } else {
                    $total = $db->getValue($tmpSql);
                    Cache::set($cacheKey, $total, 600);
                }
            } else {
                $total = $db->getValue($tmpSql);
            }

            $rows = null;
            $tmpSql = $sql . ' LIMIT ' . $offset . ', ' . $limit;
            if ($cache) {
                $cacheKey = 'DailyAggsReport:' . $tmpSql;
                $cacheValue = Cache::get($cacheKey);
                if ($cacheValue) {
                    $rows = $cacheValue;
                } else {
                    $rows = $db->getObjects($tmpSql);
                    Cache::set($cacheKey, $rows, 600);
                }
            } else {
                $rows = $db->getObjects($tmpSql);
            }

            $currentDate = date('Y-m-d');
            foreach ($rows as &$row) {

                $aggsDate = $row->aggs_date;
                $aggsKey = $row->aggs_key;

                $aggsKeyName = $aggsKey;
                if (isset($this->config['aggsKey']['keyValues']) && is_array($this->config['aggsKey']['keyValues']) ) {
                    if (isset($this->config['aggsKey']['keyValues'][$aggsKey])) {
                        $aggsKeyName = $this->config['aggsKey']['keyValues'][$aggsKey];
                    } else {
                        $aggsKeyName = '-';
                    }
                }
                $row->aggs_key_name = $aggsKeyName;

                $i = 0;
                foreach ($this->config['aggsValues'] as $aggsValue) {
                    $var = 'aggs_value_' . $i;
                    $i++;

                    // 启用缓存，并且非今天时，取缓存数据
                    if ($cache && $aggsDate != $currentDate) {

                        $cacheKey = 'DailyAggsReport:' . $aggsDate . ':' . $aggsKey . ':' . $aggsValue['sql'];
                        $cacheValue = Cache::get($cacheKey);
                        if ($cacheValue) {
                            $row->$var = $cacheValue;
                            continue;
                        }
                    }

                    $sql = $aggsValue['sql'];
                    $sql = str_replace(':aggs_date', $aggsDate, $sql);
                    $sql = str_replace(':aggs_key', $aggsKey, $sql);

                    $value = $db->getValue($sql);

                    if ($cache && $aggsDate != $currentDate) { // 启用缓存时写入
                        $cacheKey = 'DailyAggsReport:' . $aggsDate . ':' . $aggsKey . ':' . $aggsValue['sql'];
                        Cache::set($cacheKey, $value);
                    }

                    $row->$var = $value;
                }
            }

            Response::set('total', $total);
            Response::set('rows', $rows);
            Response::ajax();
        }


        Response::setTitle($this->config['name']);
        Response::display('DailyAggsReport.lists');
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
        $headers[] = '日期';
        $headers[] = $this->config['aggsKey']['name'];
        foreach ($this->config['aggsValues'] as $aggsValue) {
            $headers[] = $aggsValue['name'];
        }
        fputcsv($handler, $headers);

        $sql = $this->config['aggsKey']['sql'];
        $where = $this->buildWhere(Request::post(null, null, ''));
        if ($where) {
            $hasWhere = false;
            $pos = strpos(strtoupper($sql), ' WHERE ');
            if ($pos !== false) {
                $pos2 = strpos($sql, ')');
                if ($pos === false) {
                    $hasWhere = true;
                } else {
                    if ($pos > $pos2) {
                        $hasWhere = true;
                    }
                }
            }

            $sql .= $hasWhere ? ' AND ' : ' WHERE ';
            $sql .= $where;
        }

        if (isset($this->config['aggsKey']['orderBy'])) {
            $sql .= ' ' . $this->config['aggsKey']['orderBy'];
        } else {
            $sql .= ' ORDER BY aggs_date DESC';
        }

        $db = Be::getDb();

        $rows = $db->getYieldObjects($sql);

        $currentDate = date('Y-m-d');

        $cache = false;
        if (isset($this->config['cache'])) {
            $cache = $this->config['cache'];
        }

        foreach ($rows as $row) {

            $aggsDate = $row->aggs_date;
            $aggsKey = $row->aggs_key;

            $aggsKeyName = $aggsKey;
            if (isset($this->config['aggsKey']['keyValues']) && is_array($this->config['aggsKey']['keyValues']) ) {
                if (isset($this->config['aggsKey']['keyValues'][$aggsKey])) {
                    $aggsKeyName = $this->config['aggsKey']['keyValues'][$aggsKey];
                } else {
                    $aggsKeyName = '-';
                }
            }
            $row->aggs_key_name = $aggsKeyName;

            $values = array();
            $values[] = $aggsDate;
            $values[] = $aggsKeyName;

            foreach ($this->config['aggsValues'] as $aggsValue) {

                // 启用缓存，并且非今天时，取缓存数据
                if ($cache && $aggsDate != $currentDate) {

                    $cacheKey = 'DailyAggsReport:' . $aggsDate . ':' . $aggsKey . ':' . $aggsValue['sql'];
                    $cacheValue = Cache::get($cacheKey);
                    if ($cacheValue) {
                        $values[] = $cacheValue;
                        continue;
                    }
                }

                $sql = $aggsValue['sql'];
                $sql = str_replace(':aggs_date', $aggsDate, $sql);
                $sql = str_replace(':aggs_key', $aggsKey, $sql);
                $value = $db->getValue($sql);

                if ($cache && $aggsDate != $currentDate) { // 启用缓存时写入
                    $cacheKey = 'DailyAggsReport:' . $aggsDate . ':' . $aggsKey . ':' . $aggsValue['sql'];
                    Cache::set($cacheKey, $value);
                }

                $values[] = $value;
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
            return implode(' AND ', $wheres);
        }

        return '';
    }


}
