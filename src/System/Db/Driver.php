<?php
namespace Haitun\Service\TpAdmin\System\Db;

use Haitun\Service\TpAdmin\System\CacheProxy;

/**
 * 数据库类
 */
class Driver
{
    /**
     * @var \PDO
     */
    protected $connection = null; // 数据库连接

    /**
     * @var \PDOStatement
     */
    protected $statement = null; // 预编译 sql

    protected $config = [];

    protected $transactions = 0; // 开启的事务数，防止嵌套

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 启动缓存代理
     *
     * @param int $expire 超时时间
     * @return CacheProxy | Driver
     */
    public function withCache($expire = 600)
    {
        return new CacheProxy($this, $expire);
    }

    /**
     * 连接数据库
     *
     * @return bool 是否连接成功
     * @throws
     */
    public function connect()
    {
        return true;
    }

    /**
     * 关闭数据库连接
     *
     * @return bool 是否关闭成功
     */
    public function close()
    {
        if ($this->connection) $this->connection = null;
        return true;
    }

    /**
     * 执行 sql 语句
     *
     * @param string $sql 查询语句
     * @return \PDOStatement
     * @throws DbException
     */
    public function prepare($sql, array $driverOptions = [])
    {
        $this->connect();

        $statement = $this->connection->prepare($sql, $driverOptions);
        if (!$statement) {
            throw new DbException($statement->errorCode() . '：' . $statement->errorInfo() . ' SQL=' . $sql);
        }

        return $statement;
    }

    /**
     * 执行 sql 语句
     *
     * @param string $sql 查询语句
     * @param array $bind 占位参数
     * @return \PDOStatement
     * @throws DbException
     */
    public function execute($sql = null, $bind = [])
    {
        $statement = null;

        if (count($bind) > 0) {
            $statement = $this->prepare($sql);

            if (!$statement->execute($bind)) {
                $error = $statement->errorInfo();
                //print_r($error);
                throw new DbException($error[1] . '：' . $error[2]);
            }
        } else {

            if (!isset($this->connection)) $this->connect();

            $statement = $this->connection->query($sql);
            if ($statement === false) {
                $error = $this->connection->errorInfo();
                // print_r($error);
                throw new DbException($error[1] . '：' . $error[2] . ' SQL=' . $sql);
            }
        }

        return $statement;
    }

    /**
     * 返回单一查询结果, 多行多列记录时, 只返回第一行第一列
     *
     * @param string $sql 查询语句
     * @param array $bind 参数
     * @return string
     */
    public function getValue($sql = null, $bind = [])
    {
        $statement = $this->execute($sql, $bind);
        $tuple = $statement->fetch(\PDO::FETCH_NUM);
        $statement->closeCursor();
        return $tuple[0];
    }

    /**
     * 返回查询单列结果的数组
     *
     * @param string $sql 查询语句
     * @param array $bind 参数
     * @return array
     */
    public function getValues($sql = null, $bind = [])
    {
        $statement = $this->execute($sql, $bind);
        $values = $statement->fetchAll(\PDO::FETCH_COLUMN);
        $statement->closeCursor();
        return $values;
    }

    /**
     * 返回一个跌代器数组
     *
     * @param string $sql 查询语句
     * @param array $bind 参数
     * @return void
     */
    public function getYieldValues($sql = null, $bind = [])
    {
        $statement = $this->execute($sql, $bind);
        while ($tuple = $statement->fetch(\PDO::FETCH_NUM)) {
            yield $tuple[0];
        }
        $statement->closeCursor();
    }

    /**
     * 返回键值对数组
     * 查询两个或两个以上字段，第一列字段作为 key, 乘二列字段作为 value，多于两个字段时忽略
     *
     * @param string $sql 查询语句
     * @param array $bind 参数
     * @return array
     */
    public function getKeyValues($sql = null, $bind = [])
    {
        $statement = $this->execute($sql, $bind);
        $keyValues = $statement->fetchAll(\PDO::FETCH_UNIQUE | \PDO::FETCH_COLUMN);
        $statement->closeCursor();
        return $keyValues;
    }

    /**
     * 返回一个数组
     *
     * @param string $sql 查询语句
     * @param array $bind 参数
     * @return array
     */
    public function getArray($sql = null, $bind = [])
    {
        $statement = $this->execute($sql, $bind);
        $array = $statement->fetch(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $array;
    }

    /**
     * 返回一个二维数组
     *
     * @param string $sql 查询语句
     * @param array $bind 参数
     * @return array
     */
    public function getArrays($sql = null, $bind = [])
    {
        $statement = $this->execute($sql, $bind);
        $arrays = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $arrays;
    }

    /**
     * 返回一个跌代器二维数组
     *
     * @param string $sql 查询语句
     * @param array $bind 参数
     * @return void
     */
    public function getYieldArrays($sql = null, $bind = [])
    {
        $statement = $this->execute($sql, $bind);
        while ($result = $statement->fetch(\PDO::FETCH_ASSOC)) {
            yield $result;
        }
        $statement->closeCursor();
    }

    /**
     * 返回一个带下标索引的二维数组
     *
     * @param string $sql 查询语句
     * @param array $bind 参数
     * @param string $key 作为下标索引的字段名
     * @return array
     */
    public function getKeyArrays($sql = null, $bind = [], $key)
    {
        $statement = $this->execute($sql, $bind);
        $arrays = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        $result = [];
        foreach ($arrays as $array) {
            $result[$array[$key]] = $array;
        }

        return $result;
    }

    /**
     * 返回一个数据库记录对象
     *
     * @param string $sql 查询语句
     * @param array $bind 参数
     * @return object
     */
    public function getObject($sql = null, $bind = [])
    {
        $statement = $this->execute($sql, $bind);
        $object = $statement->fetchObject();
        $statement->closeCursor();
        return $object;
    }

    /**
     * 返回一个对象数组
     *
     * @param string $sql 查询语句
     * @param array $bind 参数
     * @return array(object)
     */
    public function getObjects($sql = null, $bind = [])
    {
        $statement = $this->execute($sql, $bind);
        $objects = $statement->fetchAll(\PDO::FETCH_OBJ);
        $statement->closeCursor();
        return $objects;
    }

    /**
     * 返回一个跌代器对象数组
     *
     * @param string $sql 查询语句
     * @param array $bind 参数
     * @return void
     */
    public function getYieldObjects($sql = null, $bind = [])
    {
        $statement = $this->execute($sql, $bind);
        while ($result = $statement->fetchObject()) {
            yield $result;
        }
        $statement->closeCursor();
    }

    /**
     * 返回一个带下标索引的对象数组
     *
     * @param string $sql 查询语句
     * @param array $bind 参数
     * @param string $key 作为下标索引的字段名
     * @return array(object)
     */
    public function getKeyObjects($sql = null, $bind = [], $key)
    {
        $statement = $this->execute($sql, $bind);
        $objects = $statement->fetchAll(\PDO::FETCH_OBJ);
        $statement->closeCursor();

        $result = [];
        foreach ($objects as $object) {
            $result[$object->$key] = $object;
        }
        return $result;
    }

    /**
     * 插入一个对象到数据库
     *
     * @param string $table 表名
     * @param object /array(object) $obj 要插入数据库的对象或对象数组，对象属性需要和该表字段一致
     */
    public function insert($table, $obj)
    {
        // 批量插入
        if (is_array($obj)) {
            $vars = get_object_vars($obj[0]);
            $sql = 'INSERT INTO ' . $table . '(' . implode(',', array_keys($vars)) . ') VALUES(' . implode(',', array_fill(0, count($vars), '?')) . ')';
            $statement = $this->prepare($sql);
            foreach ($obj as $o) {
                $vars = get_object_vars($o);
                $statement->execute(array_values($vars));
            }
            $statement->closeCursor();
        } else {
            $vars = get_object_vars($obj);
            $sql = 'INSERT INTO ' . $table . '(' . implode(',', array_keys($vars)) . ') VALUES(' . implode(',', array_fill(0, count($vars), '?')) . ')';
            $statement = $this->execute($sql, array_values($vars));
            $statement->closeCursor();
        }
    }

    /**
     * 更新一个对象到数据库
     *
     * @param string $table 表名
     * @param object $obj 要插入数据库的对象，对象属性需要和该表字段一致
     * @param string $primaryKey 主键
     * @throws DbException
     */
    public function update($table, $obj, $primaryKey)
    {
        $fields = [];
        $fieldValues = [];

        $where = null;
        $whereValue = null;

        foreach (get_object_vars($obj) as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            // 主键不更新
            if ($key == $primaryKey) {
                $where = '' . $key . '=?';
                $whereValue = $value;
                continue;
            }
            if ($value === null) {
                continue;
            } else {
                $fields[] = '' . $key . '=?';
                $fieldValues[] = $value;
            }
        }

        if ($where == null) {
            throw new DbException('更新数据时未指定条件！');
        }

        $sql = 'UPDATE ' . $table . ' SET ' . implode(',', $fields) . ' WHERE ' . $where;
        $fieldValues[] = $whereValue;

        $statement = $this->execute($sql, $fieldValues);
        $statement->closeCursor();
    }

    /**
     * 处理字符串防止 SQL 注入
     *
     * @param string $string 字符串
     * @return string
     */
    public function quote($string)
    {
        $this->connect();
        return $this->connection->quote($string);
    }

    /**
     * 获取 insert 插入后产生的 id
     *
     * @return int
     */
    public function getLastInsertId()
    {
        $this->connect();
        return $this->connection->lastInsertId();
    }

    /**
     * 获取当前数据库所有表名
     *
     * @return array
     */
    public function getTables()
    {
        return $this->getObjects('SHOW TABLES');
    }

    /**
     * 获取一个表的字段列表
     *
     * @param string $table 表名
     * @return array
     */
    public function getTableFields($table)
    {
        $fields = $this->getObjects('SHOW FIELDS FROM ' . $table);

        $data = [];
        foreach ($fields as $field) {
            $data[$field->Field] = $field;
        }
        return $data;
    }

    /**
     * 删除表
     *
     * @param string $table 表名
     */
    public function dropTable($table)
    {
        $statement = $this->execute('DROP TABLE IF EXISTS ' . $table);
        $statement->closeCursor();
    }

    /**
     * 开启事务处理
     *
     */
    public function startTransaction()
    {
        $this->beginTransaction();
    }

    public function beginTransaction()
    {
        $this->connect();

        $this->transactions++;
        if ($this->transactions == 1) {
            $this->connection->beginTransaction();
        }
    }

    /**
     * 事务回滚
     */
    public function rollback()
    {
        $this->connect();
        $this->transactions--;
        if ($this->transactions == 0) {
            $this->connection->rollBack();
        }
    }

    /**
     * 事务提交
     */
    public function commit()
    {
        $this->connect();

        $this->transactions--;
        if ($this->transactions == 0) {
            $this->connection->commit();
        }
    }

    /**
     * 是否在事务中
     *
     * @return bool
     */
    public function inTransaction()
    {
        $this->connect();
        return $this->connection->inTransaction();
    }

    /**
     * 获取数据库连接对象
     *
     * @return \PDO
     */
    public function getConnection()
    {
        $this->connect();
        return $this->connection;
    }

    /**
     * 获取 版本号
     *
     * @return string
     */
    public function getVersion()
    {
        $this->connect();
        return $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }
}