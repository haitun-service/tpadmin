<?php
namespace Haitun\Service\TpAdmin;

use Haitun\Service\TpAdmin\System\Be;
use Haitun\Service\TpAdmin\System\Cookie;
use Haitun\Service\TpAdmin\System\Request;
use Haitun\Service\TpAdmin\System\Response;

/**
 * Trait TableManager 表管理器
 * @package Haitun\Service\TpAdmin 海豚服务 管理框架
 */
trait TableManager
{
    use Base;

    /*
     * 在子类中必须定义 $config 属性，示例值如下：
    protected $config = array(
        'base' => array(
            'name' => '用户管理',
            'table' => 'user'
        ),

        'lists' => array(

            'toolbar' => array(
                'create' => '新建',
                'export' => '导出'
            ),

            'action' => array(
                'detail' => '查看',
                'edit' => '编辑',
                'delete' => '删除',
            ),
        ),

        'detail' => array(
            'tabs' => array()
        ),

        'create' => array(),

        'edit' => array(),

        'delete' => array(
            'field' => 'is_delete'
        ),

        'export' => array(),
    );
    */

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
            ->setDirData('data')
            ->setDirCache('runtime/m/cache');
    }
    
    /**
     * 列表展示
     */
    public function lists()
    {

        $table = Be::getTable($this->config['base']['table']);
        $primaryKey = $table->getPrimaryKey();

        if (Request::isPost()) {

            $this->buildWhere($table, Request::post(null, null, ''));

            $offset = Request::post('offset', 0);
            $limit = Request::post('limit', 0, 'int');
            if ($limit < 0) $limit = 0;
            if (!$limit) {
                $cookieLimit = Cookie::get($this->config['base']['table'] . ':limit', 0, 'int');
                if ($cookieLimit) {
                    $limit = $cookieLimit;
                } else {
                    $limit = 12;
                }
            } else {
                Cookie::set($this->config['base']['table'] . ':limit', $limit, 86400 * 30);
            }

            $total = $table->count();

            $table->offset($offset)->limit($limit);

            $orderBy = Request::post('sort', $primaryKey);
            $orderByDir = Request::post('order', 'DESC');
            $table->orderBy($orderBy, $orderByDir);

            $lists = $table->getObjects();

            $fields = $table->getFields();
            foreach ($lists as &$x) {
                $this->formatData($x, $fields);
            }

            Response::set('total', $total);
            Response::set('rows', $lists);
            Response::ajax();
        }

        Response::setTitle($this->config['base']['name'] . ' - 列表');
        Response::set('table', $table);
        Response::set('config', $this->config);

        Response::display('TableManager.lists');
    }

    /**
     * 明细
     */
    public function detail()
    {
        $row = Be::getRow($this->config['base']['table']);

        $primaryKey = $row->getPrimaryKey();
        $primaryKeyValue = Request::get($primaryKey, null);

        if (!$primaryKeyValue) {
            Response::error('参数（' . $primaryKey . '）缺失！');
        }

        $row->load($primaryKeyValue);
        if (!$row->$primaryKey) {
            Response::error('主键编号（' . $primaryKey . '）为 ' . $primaryKeyValue . ' 的记录不存在！');
        }

        $fields = $row->getFields();
        $this->formatData($row, $fields);

        Response::setTitle($this->config['base']['name'] . ' - 明细');
        Response::set('row', $row);
        Response::display('TableManager.detail');
    }

    /**
     * 创建
     */
    public function create()
    {
        $row = Be::getRow($this->config['base']['table']);

        if (Request::isPost()) {

            Be::getDb()->startTransaction();
            try {
                $row->bind(Request::post());
                $primaryKey = $row->getPrimaryKey();
                unset($row->$primaryKey);
                $row->save();

                Be::getDb()->commit();
            } catch (\Exception $e) {

                Be::getDb()->rollback();
                Response::error($e->getMessage());
            }

            Response::success('创建成功！');

        } else {
            Response::setTitle($this->config['base']['name'] . ' - 创建');
            Response::set('row', $row);
            Response::display('TableManager.create');
        }
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $row = Be::getRow($this->config['base']['table']);
        $primaryKey = $row->getPrimaryKey();
        $primaryKeyValue = Request::get($primaryKey, null);

        if (!$primaryKeyValue) {
            Response::error('参数（' . $primaryKey . '）缺失！');
        }

        $row->load($primaryKeyValue);
        if (!$row->$primaryKey) {
            Response::error('主键编号（' . $primaryKey . '）为 ' . $primaryKeyValue . ' 的记录不存在！');
        }

        if (Request::isPost()) {

            Be::getDb()->startTransaction();
            try {
                $row->bind(Request::post());
                $row->save();

                Be::getDb()->commit();
            } catch (\Exception $e) {

                Be::getDb()->rollback();
                Response::error($e->getMessage());
            }

            Response::success('修改成功！');

        } else {

            Response::setTitle($this->config['base']['name'] . ' - 编辑');
            Response::set('row', $row);
            Response::display('TableManager.edit');
        }
    }

    /*
     * 导出
     */
    public function export()
    {
        $table = Be::getTable($this->config['base']['table']);

        $this->buildWhere($table, Request::post(null, null, ''));

        $lists = $table->getYieldArrays();

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename='. date('YmdHis').'.csv');
        $handler = fopen('php://output', 'w') or die("can't open php://output");
        fwrite($handler, pack('H*', 'EFBBBF')); // 写入 BOM 头

        $headers = array();
        $fields = $table->getFields();
        foreach ($fields as $field) {
            if ($field['disable']) continue;

            $headers[] = $field['name'];
        }
        fputcsv($handler, $headers);


        $fields = $table->getFields();
        foreach ($lists as &$x) {

            $this->formatData($x, $fields);

            fputcsv($handler, $x);
        }
        fclose($handler) or die("can't close php://output");
    }

    /**
     * 删除
     */
    public function delete()
    {
        $row = Be::getRow($this->config['base']['table']);
        $primaryKey = $row->getPrimaryKey();
        $primaryKeyValue = Request::get($primaryKey, null);

        if (!$primaryKeyValue) {
            Response::error('参数（' . $primaryKey . '）缺失！');
        }

        Be::getDb()->startTransaction();
        try {

            if (is_array($primaryKeyValue)) {
                foreach ($primaryKeyValue as $x) {
                    $row->delete($x);
                }
            } else {
                $row->delete($primaryKeyValue);
            }

            Be::getDb()->commit();
        } catch (\Exception $e) {

            Be::getDb()->rollback();
            Response::error($e->getMessage());
        }

        Response::success('创建成功！');
    }

    /**
     * 配置项
     */
    public function setting()
    {
        $table = Be::getTable($this->config['base']['table']);

        if (Request::isPost()) {

            $fieldItems = Request::post('field');
            $nameItems = Request::post('name');
            $optionTypeItems = Request::post('optionType');
            $optionDataItems = Request::post('optionData');
            $disableItems = Request::post('disable');
            $showItems = Request::post('show');
            $editableItems = Request::post('editable');
            $createItems = Request::post('create');
            $formatItems = Request::post('format');


            $len = count($fieldItems);

            $formattedFields = array();
            for ($i = 0; $i < $len; $i++) {
                $formattedFields[$fieldItems[$i]] = array(
                    'field' => $fieldItems[$i],
                    'name' => $nameItems[$i],
                    'optionType' => $optionTypeItems[$i],
                    'optionData' => $optionDataItems[$i],
                    'disable' => $disableItems[$i],
                    'show' => $showItems[$i],
                    'editable' => $editableItems[$i],
                    'create' => $createItems[$i],
                    'format' => $formatItems[$i],
                );
            }

            $serviceSystem = Be::getService('Cache');
            $serviceSystem->updateTableConfig($this->config['base']['table'], $formattedFields);

            Response::success('修改配置成功！');

        } else {

            Response::setTitle($this->config['base']['name'] . ' - 配置');
            Response::set('table', $table);
            Response::display('TableManager.setting');
        }
    }

    public function chartPie() {

        // 聚合字段
        $aggField = Request::get('aggField', '');
        $aggLimit = Request::get('aggLimit', 10, 'int');

        $table = Be::getTable($this->config['base']['table']);

        Response::set('table', $table);
        Response::set('aggField', $aggField);

        if ($aggField) {
            $aggData = $table->groupBy($aggField)
                ->limit($aggLimit)
                ->getObjects($aggField.', COUNT(*) quantity');

            Response::set('table', $table);
            Response::set('aggField', $aggField);
            Response::set('aggData', $aggData);
        }

        Response::setTitle($this->config['base']['name'] . ' - 饼图');
        Response::display('TableManager.chartPie');
    }


    /**
     * @param \Haitun\Service\TpAdmin\System\Table $table
     * @param $condition
     */
    protected function buildWhere($table, $condition) {

        $len = count($condition['conditionField']);
        if ($len > 0) {

            for ($i = 0; $i <= $len; $i++) {
                $conditionField = $condition['conditionField'][$i];
                $conditionOperator = $condition['conditionOperator'][$i];
                $conditionValue = trim($condition['conditionValue'][$i]);

                if ($conditionValue === '') continue;

                switch ($conditionOperator) {
                    case '=':
                    case '>':
                    case '>=':
                    case '<':
                    case '<=':
                        $table->where($conditionField, $conditionOperator, $conditionValue);
                        break;

                    case 'like':
                        $table->where($conditionField, 'LIKE', '%'.$conditionValue.'%');
                        break;
                    case 'like1':
                        $table->where($conditionField, 'LIKE', $conditionValue.'%');
                        break;
                    case 'like2':
                        $table->where($conditionField, 'LIKE', '%'.$conditionValue);
                        break;
                }
            }
        }
    }

    protected function formatData($data, $fields)
    {
        foreach ($fields as $field) {

            $f = $field['field'];

            if ($field['disable']) {
                unset($data->$f);
                continue;
            }

            if ($field['optionType'] != 'null') {

                $keyValues = $field['option']->getKeyValues();

                if (isset($keyValues[$data->$f])) {
                    $data->$f = $keyValues[$data->$f];
                } else {
                    $data->$f = '-';
                }
            } else {

                if ($field['format']) {

                    switch ($field['format']) {

                        case 'date(Ymd)':
                            $data->$f = date('Y-m-d', $data->$f);
                            break;

                        case 'date(YmdHi)':
                            $data->$f = date('Y-m-d H:i', $data->$f);
                            break;

                        case 'date(YmdHis)':
                            $data->$f = date('Y-m-d H:i:s', $data->$f);
                            break;

                    }

                }

            }
        }

        return $data;
    }
}
