<?php
namespace Haitun\Service\TpAdmin\System;

use Haitun\Service\TpAdmin\System\Db\DbException;

/**
 * 数据库表行记录
 */
abstract class Row
{

    /**
     * 表名
     *
     * @var string
     */
    protected $tableName = '';

    /**
     * 主键
     *
     * @var string
     */
    protected $primaryKey = 'id'; // 主键

    /**
     * 字段明细列表
     *
     * @var array
     */
    protected $fields = array();


    protected $quote = '`'; // 字段或表名转义符 mysql: `


    /**
     * 绑定一个数据源， GET, POST, 或者一个数组, 对象
     *
     * @param string | array | object $data 要绑定的数据对象
     * @return \Haitun\Service\TpAdmin\System\row | bool
     * @throws DbException
     */
    public function bind($data)
    {
        if (!is_object($data) && !is_array($data)) {
            throw new DbException('绑定失败，不合法的数据源！');
        }

        if (is_object($data)) $data = get_object_vars($data);

        $properties = get_object_vars($this);

        foreach ($properties as $key => $value) {
            if (isset($data[$key])) {
                $val = $data[$key];
                $this->$key = $val;
            }
        }

        return $this;
    }

    /**
     * 加载记录
     *
     * @param string|int|array $field 要加载数据的键名，$val == null 时，为指定的主键值加载，
     * @param string $value 要加载的键的值
     * @return \Haitun\Service\TpAdmin\System\row | false
     * @throws DbException
     */
    public function load($field, $value = null)
    {
        $sql = null;
        $values = [];

        if ($value === null) {
            if (is_array($field)) {
                $sql = 'SELECT * FROM ' . $this->quote . $this->tableName . $this->quote . ' WHERE';
                foreach ($field as $key => $val) {
                    $sql .= ' ' . $this->quote . $key . $this->quote . '=? AND';
                    $values[] = $val;
                }
                $sql = substr($sql, 0, -4);
            } elseif (is_numeric($field)) {
                $sql = 'SELECT * FROM ' . $this->quote . $this->tableName . $this->quote . ' WHERE ' . $this->quote . $this->primaryKey . $this->quote . ' = \'' . intval($field) . '\'';
            } elseif (is_string($field)) {
                $sql = 'SELECT * FROM ' . $this->quote . $this->tableName . $this->quote . ' WHERE ' . $field;
            }
        } else {
            if (is_array($field)) {
                throw new DbException('row->load() 方法参数错误！');
            }
            $sql = 'SELECT * FROM ' . $this->quote . $this->tableName . $this->quote . ' WHERE ' . $this->quote . $field . $this->quote . '=?';
            $values[] = $value;
        }

        $db = Be::getDb();
        $row = $db->getObject($sql, $values);

        if (!$row) {
            throw new DbException('未找到指定数据记录！');
        }

        return $this->bind($row);
    }

    /**
     * 保存数据到数据库
     *
     * @return bool
     */
    public function save()
    {
        $db = Be::getDb();

        $primaryKey = $this->primaryKey;
        if ($this->$primaryKey) {
            $db->update($this->tableName, $this, $this->primaryKey);
        } else {
            $db->insert($this->tableName, $this);
            $this->$primaryKey = $db->getLastInsertId();
        }

        return true;
    }

    /**
     * 删除指定主键值的记录
     *
     * @param int $id 主键值
     * @return bool
     * @throws DbException
     */
    public function delete($id = null)
    {
        $primaryKey = $this->primaryKey;
        if ($id === null) $id = $this->$primaryKey;

        if ($id === null) {
            throw new DbException('参数缺失, 请指定要删除记录的编号！');
        }

        $db = Be::getDb();
        $db->execute('DELETE FROM ' . $this->quote . $this->tableName . $this->quote . ' WHERE ' . $this->quote . $this->primaryKey . $this->quote . '=?', array($id));

        return true;
    }

    /**
     * 自增某个字段
     *
     * @param string $field 字段名
     * @param int $step 自增量
     * @return bool
     */
    public function increment($field, $step = 1)
    {
        $primaryKey = $this->primaryKey;
        $id = $this->$primaryKey;
        $sql = 'UPDATE ' . $this->quote . $this->tableName . $this->quote . ' SET ' . $this->quote . $field . $this->quote . '=' . $this->quote . $field . $this->quote . '+' . $step . ' WHERE ' . $this->quote . $this->primaryKey . $this->quote . '=?';

        $db = Be::getDb();
        $db->execute($sql, array($id));

        return true;
    }

    /**
     * 自减某个字段
     *
     * @param string $field 字段名
     * @param int $step 自减量
     * @return bool
     */
    public function decrement($field, $step = 1)
    {
        $primaryKey = $this->primaryKey;
        $id = $this->$primaryKey;
        $sql = 'UPDATE ' . $this->quote . $this->tableName . $this->quote . ' SET ' . $this->quote . $field . $this->quote . '=' . $this->quote . $field . $this->quote . '-' . $step . ' WHERE ' . $this->quote . $this->primaryKey . $this->quote . '=?';

        $db = Be::getDb();
        $db->execute($sql, array($id));

        return true;
    }

    /**
     * 转成简单数组
     *
     * @return array
     */
    public function toArray() {
        $array = get_object_vars($this);
        unset($array['db'], $array['tableName'], $array['primaryKey'], $array['quote']);

        return $array;
    }

    /**
     * 转成简单对象
     *
     * @return Object
     */
    public function toObject() {
        return (Object) $this->toArray();
    }


    /**
     * 获取表名
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * 获取主键名
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * 获取字段明细列表
     *
     * @return array
     */
    public function getFields()
    {
        foreach ($this->fields as &$field) {

            if (!isset($field['option'])) {
                switch ($field['optionType']) {

                    case 'null':
                        $field['option'] = new \Haitun\Service\TpAdmin\Option\NullOption('');
                        break;
                    case 'array':
                        $field['option'] = new \Haitun\Service\TpAdmin\Option\ArrayOption($field['optionData']);
                        break;
                    case 'sql':
                        $field['option'] = new \Haitun\Service\TpAdmin\Option\SqlOption($field['optionData']);
                        break;

                }
            }
        }
        return $this->fields;
    }

    /**
     * 获取指定字段
     *
     * @param string $fieldName 字段名
     * @return array
     */
    public function getField($fieldName)
    {
        if (isset($this->fields[$fieldName])) {

            if (!isset($this->fields[$fieldName]['option'])) {
                switch ($this->fields[$fieldName]['optionType']) {

                    case 'null':
                        $this->fields[$fieldName]['option'] = new \Haitun\Service\TpAdmin\Option\NullOption('');
                        break;
                    case 'array':
                        $this->fields[$fieldName]['option'] = new \Haitun\Service\TpAdmin\Option\ArrayOption($this->fields[$fieldName]['optionData']);
                        break;
                    case 'sql':
                        $this->fields[$fieldName]['option'] = new \Haitun\Service\TpAdmin\Option\SqlOption($this->fields[$fieldName]['optionData']);
                        break;

                }
            }

            return $this->fields[$fieldName];
        }

        return null;
    }


}
