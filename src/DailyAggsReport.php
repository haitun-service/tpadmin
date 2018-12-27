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
     * 在子类中必须定义 $config 属性，示例值如下：
    */
    public $config = array(

        'name' => '仓库每日拣货核对汇总',

        'search' => array(
            'warehouse_id' => array(
                'name' => '仓库',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemInt',
                'uiType' => 'select',
                'keyValueType' => 'sql',
                'keyValues' => ''
            ),

            'create_time' => array(
                'name' => '日期',
                'driver' => '\Haitun\Service\TpAdmin\SearchItem\SearchItemDateRange'
            )
        ),

        'aggsKey' => array(
            'name' => '仓库',
            'sql' => 'SELECT DISTINCT DATE_FORMAT(create_time, \'%Y-%m-%d\') AS aggs_date, warehouse_id AS aggs_key FROM ws_pick_detail',
            'keyValueType' => 'sql',
            'keyValues' => ''
        ),

        'aggsValues' => array(
            array(
                'name' => '总新增订单数',
                'sql' => 'SELECT COUNT(*) FROM ws_pick_detail WHERER create_time>=\':aggs_date 00:00:00\' AND create_time<=\':aggs_date 23:59:59\' AND warehouser_id=:aggs_key'
            ),
            array(
                'name' => '总生拣货任务数',
                'sql' => 'SELECT COUNT(*) FROM ws_pick_detail WHERER create_time>=\':aggs_date 00:00:00\' AND create_time<=\':aggs_date 23:59:59\' AND warehouser_id=:aggs_key'
            ),
            array(
                'name' => '总核对订单数',
                'sql' => 'SELECT COUNT(*) FROM ws_pick_detail WHERER create_time>=\':aggs_date 00:00:00\' AND create_time<=\':aggs_date 23:59:59\' AND warehouser_id=:aggs_key'
            ),
            array(
                'name' => '总交运订单数',
                'sql' => 'SELECT COUNT(*) FROM ws_pick_detail WHERER create_time>=\':aggs_date 00:00:00\' AND create_time<=\':aggs_date 23:59:59\' AND warehouser_id=:aggs_key'
            )
        ),

        'cache' => true,
    );


    public function __construct()
    {
        parent::__construct();

        Be::getRuntime()->setDbConfig(array(
            'host' => config('database.hostname'), // 主机名
            'port' => config('database.hostport'), // 端口号
            'user' => config('database.username'), // 用户名
            'pass' => config('database.password'), // 密码
            'name' => config('database.database') // 数据库名称
        ))
            ->setFramework('tp5')
            ->setPathRoot(dirname(APP_PATH))
            ->setDirData('public/tpadmin/data')
            ->setDirCache('runtime/tpadmin/cache');
    }

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

            $sql = $this->config['aggsKey']['sql'];
            $where = $this->buildWhere(Request::post(null, null, ''));
            if ($where) {
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
                    $rows = $db->getValue($tmpSql);
                    Cache::set($cacheKey, $rows, 600);
                }
            } else {
                $rows = $db->getValue($tmpSql);
            }

            $currentDate = date('Y-m-d');
            foreach ($rows as &$row) {

                $aggsDate = $row->aggs_date;
                $aggsKey = $row->aggs_key;

                $i = 0;
                foreach ($this->config['aggsValues'] as $aggsValue) {

                    $var = 'aggs_value_' . $i;

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
                    $value = $db->getValue($sql, array(':aggs_date' => $aggsDate, ':aggs_key' => $aggsKey));

                    if ($cache && $aggsDate != $currentDate) { // 启用缓存时写入
                        $cacheKey = 'DailyAggsReport:' . $aggsDate . ':' . $aggsKey . ':' . $aggsValue['sql'];
                        Cache::set($cacheKey, $value);
                    }

                    $row->$var = $value;
                    $i++;
                }
            }

            Response::set('total', $total);
            Response::set('rows', $rows);
            Response::set('config', $this->config);
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

        foreach ($rows as &$row) {

            $aggsDate = $row->aggs_date;
            $aggsKey = $row->aggs_key;

            $values = array();
            $values[] = $aggsDate;
            $values[] = $aggsKey;

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
                $value = $db->getValue($sql, array(':aggs_date' => $aggsDate, ':aggs_key' => $aggsKey));

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
            return ' WHERE '. implode(' AND ', $wheres);
        }

        return '';
    }


}
