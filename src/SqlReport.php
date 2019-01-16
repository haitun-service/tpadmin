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

    /*
     * 配置项
    */
    public $config = array();

    /**
     * 列表展示
     */
    public function lists()
    {
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
                $pos = strpos($sql, ':where');
                if ($pos !== false) {
                    $sql = str_replace(':where', ' AND '.$where, $sql);
                } else {
                    $sqlUpper = strtoupper($sql);
                    $hasWhere = false;
                    $pos = strpos($sqlUpper, ' WHERE ');
                    if ($pos !== false) {
                        $pos2 = strpos($sqlUpper, ')');
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
            } else {
                $sql = str_replace(':where', '', $sql);
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
                $pos = strpos($sql, ':where');
                if ($pos !== false) {
                    $sql = str_replace(':where', ' AND '.$where, $sql);
                } else {
                    $sqlUpper = strtoupper($sql);
                    $hasWhere = false;
                    $pos = strpos($sqlUpper, ' WHERE ');
                    if ($pos !== false) {
                        $pos2 = strpos($sqlUpper, ')');
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
            } else {
                $sql = str_replace(':where', '', $sql);
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
                        if (isset($field['keyValues'][$row->$key])) {
                            $row->$key = $field['keyValues'][$row->$key];
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

        Response::set('config', $this->config);
        Response::setTitle($this->config['name']);
        Response::display('SqlReport.lists');
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
            $pos = strpos($sql, ':where');
            if ($pos !== false) {
                $sql = str_replace(':where', ' AND '.$where, $sql);
            } else {
                $sqlUpper = strtoupper($sql);
                $hasWhere = false;
                $pos = strpos($sqlUpper, ' WHERE ');
                if ($pos !== false) {
                    $pos2 = strpos($sqlUpper, ')');
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
        } else {
            $sql = str_replace(':where', '', $sql);
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
                    if (isset($field['keyValues'][$row->$key])) {
                        $values[] = $field['keyValues'][$row->$key];
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
            return implode(' AND ', $wheres);
        }

        return '';
    }


}
