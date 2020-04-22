<?php
class Plus_FreeSQL {


    //生成的SQL语句
    protected $sql;
    //SQL语句参数
    protected $params;
    //语句模式
    // SELECT = 1;
    // INSERT = 2;
    // UPDATE = 3;
    // DELETE = 4;
    protected $sqlMode;
    //数据库类
    protected $dataSourceIndex = 0;
    protected $mysql;

    function __construct() {
        $this->sql = '';
        $this->params = array();
    }

    /**
     * @return Plus_FreeSQL
     */
    public function _reset() {
        $this->sql = '';
        $this->params = array();
        $this->sqlMode = 0;
        return $this;
    }

    /**
     * 指定选择的列
     * @param $fields
     * @return Plus_FreeSQL
     */
    public function _select($fields) {
        $this->sql = "SELECT " . (is_string($fields) ? $fields : (is_array($fields) ? implode(',', $fields) : strval($fields)));
        $this->sqlMode = 1;
        return $this;
    }

    /**
     * 指定要插入的表
     * @param $table string
     * @param $dataSourceIndex int
     * @return Plus_FreeSQL
     */
    public function _insert_into($table, $dataSourceIndex = 0) {
        $this->sql = "INSERT INTO " . $table;
        $this->sqlMode = 2;
        $this->dataSourceIndex = $dataSourceIndex;
        return $this;
    }

    public function _values($field, $value = null) {

        if (is_string($field) && $value) {
            $this->sql .= "(" . $field . ") VALUES(?)";
            $this->params[] = $value;
        } else if (is_array($field)) {
            $fields = '';
            $values = '';
            $comma = '';
            foreach ($field as $subField => $subValue) {
                $fields .= $comma . $subField;
                $values .= $comma . '?';
                $comma = ", ";
                $this->params[] = $subValue;
            }
            $this->sql .= "(" . $fields . ") VALUES(" . $values . ")";
        }
        return $this;

    }

    /**
     * 如果指定的记录不存在才插入表
     * @param string $table
     * @param array $params
     * @param mixed $checkKeys
     * @param int $dataSourceIndex
     * @return bool|int
     */
    public function _insert_if_not_exists($table, $params, $checkKeys, $dataSourceIndex = 0) {

        if (!$table || !$params || !is_array($params) || !$checkKeys)
            return false;

        if (is_string($checkKeys)) {
            $checkKeys = explode(',', $checkKeys);
        }

        $this->sql = "INSERT INTO " . $table;
        $this->sqlMode = 2;
        $this->dataSourceIndex = $dataSourceIndex;
        $filedList = '(';
        $selectionList = '(SELECT ';
        $delemiter = '';
        foreach ($params as $fieldName => $fieldValue) {
            $filedList .= $delemiter . '`' . $fieldName . '`';
            if (is_string($fieldValue)) {
                $selectionList .= $delemiter . '\'' . $fieldValue . '\' AS `' . $fieldName . '`';
            } else {
                $selectionList .= $delemiter . $fieldValue . ' AS `' . $fieldName . '`';
            }
            $delemiter = ',';
        }
        $filedList .= ')';
        $selectionList .= ')';

        $delemiter = '';
        $conditions = '';
        foreach ($checkKeys as $checkKey) {
            $checkValue = $params[$checkKey];
            if (is_string($checkValue)) {
                $conditions .= $delemiter . '`' . $checkKey . '`=\'' . $checkValue . '\'';
            } else {
                $conditions .= $delemiter . '`' . $checkKey . '`=' . $checkValue;
            }
            $delemiter = ' AND';
        }

        $this->sql .= ' ' . $filedList . ' SELECT * FROM ' . $selectionList .
            ' AS tmp WHERE NOT EXISTS (SELECT * FROM `' . $table . '` WHERE ' . $conditions . ') LIMIT 1;';

        return $this->_execute();

    }


    /**
     * 指定要更新的表
     * @param $table string
     * @param $dataSourceIndex int
     * @return Plus_FreeSQL
     */
    public function _update($table, $dataSourceIndex = 0) {
        $this->sql = "UPDATE " . $table;
        $this->sqlMode = 3;
        $this->dataSourceIndex = $dataSourceIndex;
        return $this;
    }

    /**
     * 指定要更新的字段
     * @param $field
     * @param null $value
     * @return Plus_FreeSQL
     */
    public function _set($field, $value = null) {

        if (is_string($field) && $value) {
            $this->sql .= " SET $field = ?";
            $this->params[] = $value;
        } else if (is_array($field)) {
            $this->sql .= " SET ";
            $comma = "";
            foreach ($field as $subField => $subValue) {
                $this->sql .= $comma . $subField . " = ?";
                $comma = ", ";
                $this->params[] = $subValue;
            }
        }
        return $this;

    }

    /**
     * 删除指定表的数据
     * @param $table string
     * @param $dataSourceIndex int
     * @return Plus_FreeSQL
     */
    public function _delete_from($table, $dataSourceIndex = 0) {
        $this->sql .= "DELETE FROM " . $table;
        $this->sqlMode = 4;
        $this->dataSourceIndex = $dataSourceIndex;
        return $this;
    }

    /**
     * 指定选择的表
     * @param $table string
     * @param $dataSourceIndex int
     * @return Plus_FreeSQL
     */
    public function _from($table, $dataSourceIndex = 0) {
        $this->sql .= " FROM " . $table;
        $this->dataSourceIndex = $dataSourceIndex;
        return $this;
    }

    /**
     * 指定查询条件
     * @param $column string
     * @return Plus_FreeSQL
     */
    public function _where($column) {
        $this->sql .= " WHERE " . $column;
        return $this;
    }

    /**
     * 条件“=”
     * @param int|string $value 值
     * @return Plus_FreeSQL
     */
    public function _eq($value) {
        $this->sql .= " = ?";
        $this->params[] = $value;
        return $this;
    }

    /**
     * 条件“LIKE”
     * @param int|string $value 值
     * @return Plus_FreeSQL
     */
    public function _like($value) {
        $this->sql .= " LIKE ?";
        $this->params[] = $value;
        return $this;
    }

    /**
     * 条件“>”
     * @param int|string $value 值
     * @return Plus_FreeSQL
     */
    public function _gt($value) {
        $this->sql .= " > ?";
        $this->params[] = $value;
        return $this;
    }

    /**
     * 条件“>=”
     * @param int|string $value 值
     * @return Plus_FreeSQL
     */
    public function _gte($value) {
        $this->sql .= " >= ?";
        $this->params[] = $value;
        return $this;
    }

    /**
     * 条件“<”
     * @param int|string $value 值
     * @return Plus_FreeSQL
     */
    public function _lt($value) {
        $this->sql .= " < ?";
        $this->params[] = $value;
        return $this;
    }

    /**
     * 条件“<=”
     * @param int|string $value 值
     * @return Plus_FreeSQL
     */
    public function _lte($value) {
        $this->sql .= " <= ?";
        $this->params[] = $value;
        return $this;
    }

    /**
     * 条件“BETWEEN”
     * @param int|string $value1 值1
     * @param int|string $value2 值2
     * @return Plus_FreeSQL
     */
    public function _between($value1, $value2) {
        $this->sql .= " BETWEEN ? AND ?";
        $this->params[] = $value1;
        $this->params[] = $value2;
        return $this;
    }

    /**
     * LIMIT
     * @param int $start 开始
     * @param int $rows 行数
     * @return Plus_FreeSQL
     */
    public function _limit($start, $rows = 0) {
        $this->sql .= " LIMIT " . intval($start);
        if ($rows) $this->sql .= "," . intval($rows);
        return $this;
    }

    /**
     * ORDER BY 排序
     * @param string|array $columns 要排序的列
     * @return Plus_FreeSQL
     */
    public function _order_by($columns) {
        $this->sql .= " ORDER BY " . (is_array($columns) ? implode(',', $columns) : $columns);
        return $this;
    }

    /**
     * 条件AND
     * @param string $column
     * @return Plus_FreeSQL
     */
    public function _and($column) {
        $this->sql .= " AND " . $column;
        return $this;
    }

    /**
     * 条件OR
     * @param string $column
     * @return Plus_FreeSQL
     */
    public function _or($column) {
        $this->sql .= " OR " . $column;
        return $this;
    }

    /**
     * 执行SQL语句并返回多行结果
     * @param int fetchMode
     * @return array result
     * @throws PLus_FreeSQLException
     */
    public function _list($fetchMode = PDO::FETCH_ASSOC) {
        if ($this->sqlMode == 1) {
            $db = $this->getDB();
            $stmt = $db->prepare($this->sql);
            $stmt->execute(count($this->params) > 0 ? $this->params : null);
            return $stmt->fetchAll($fetchMode);
        } else {
            throw new Plus_FreeSQLException("You should use _select.");
        }
    }

    /**
     * 执行SQL语句并返回多行结果
     * @param int pageNum
     * @param int pageSize
     * @param int fetchMode
     * @return array result
     * @throws PLus_FreeSQLException
     */
    public function _pagedList($pageNum, $pageSize, $fetchMode = PDO::FETCH_ASSOC) {

        if ($this->sqlMode == 1 && $this->sql) {

            $parsingSQL = strtoupper($this->sql);
            $groupPos = strpos($parsingSQL, 'GROUP');
            if (!$groupPos) {
                $fromPos = strpos($parsingSQL, 'FROM') + 4;
                $orderPos = strpos($parsingSQL, 'ORDER');
                if (!$orderPos) $orderPos = strlen($parsingSQL);
                $countSQL = "SELECT COUNT(*) AS CNT FROM" . substr($this->sql, $fromPos, $orderPos - $fromPos);
            }

            if ($countSQL) {

                $db = $this->getDB();
                $stmt = $db->prepare($countSQL);
                $stmt->execute(count($this->params) > 0 ? $this->params : null);
                $countInfo = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($countInfo) {

                    $totalRecords = $countInfo['CNT'];
                    $totalPages = ceil($totalRecords / $pageSize);
                    if ($pageNum < 1) $pageNum = 1;
                    if ($pageNum > $totalPages) $pageNum = $totalPages;
                    $start = ($pageNum - 1) * $pageSize;
                    $limit = $pageSize;

                    $result = null;

                    $limitation = " LIMIT $start,$limit";
                    $sql = $this->sql . $limitation;
                    $stmt = $db->prepare($sql);
                    $stmt->execute(count($this->params) > 0 ? $this->params : null);
                    $result = $stmt->fetchAll($fetchMode);

                    return array($pageNum, $pageSize, $totalPages, $totalRecords, $result);

                } else {
                    throw new Plus_FreeSQLException("Can not get result set count, SQL:$countSQL");
                }

            } else {
                throw new Plus_FreeSQLException("Can not generate count sql!");
            }
        } else {
            throw new Plus_FreeSQLException("You should use _select.");
        }

    }

    /**
     * 执行SQL语句并返回单行结果
     * @return array
     * @throws PLus_FreeSQLException
     */
    public function _get() {
        if ($this->sqlMode == 1) {
            if (!strstr($this->sql, "LIMIT")) $this->sql .= " LIMIT 1";
            $db = $this->getDB();
            $stmt = $db->prepare($this->sql);
            $stmt->execute(count($this->params) > 0 ? $this->params : null);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            throw new Plus_FreeSQLException("You should use _select.");
        }
    }

    /**
     * 执行SQL语句并返回结果，当为INSERT时，返回自动产生的ID，当为其它时，返回影响的行数
     * @return int
     * @throws PLus_FreeSQLException
     */
    public function _execute() {

        if ($this->sqlMode != 1) {

            try {

                $db = $this->getDB();
                $stmt = $db->prepare($this->sql);
                $stmt->execute(count($this->params) > 0 ? $this->params : null);
                if ($stmt->errorCode() == '00000') {
                    if ($this->sqlMode == 2) {
                        return $db->lastInsertId();
                    } else {
                        return $stmt->rowCount();
                    }
                } else {
                    $errorInfo = $stmt->errorInfo();
                    throw new Plus_FreeSQLException(var_export($errorInfo, true));
                }

            } catch (Exception $e) {
                plus::debug($e->getMessage());
            }

        }
        return 0;

    }

    /**
     * 设置或返回sql语句
     * @param null|string SQL语句
     * @param mixed SQL语句里的参数
     * @param int $dataSourceIndex 数据源索引
     * @return string | Plus_FreeSQL
     */
    public function _sql($sql = null, $params = null, $dataSourceIndex = 0) {

        if ($sql) {

            $this->sql = trim($sql);
            if ($params !== null) {
                if (is_array($params)) {
                    $this->params = $params;
                } else {
                    $this->params[] = $params;
                }
            }
            $this->dataSourceIndex = $dataSourceIndex;
            $this->sqlMode = 1;
            if (strpos($this->sql, "INSERT") === 0) {
                $this->sqlMode = 2;
            } else if (strpos($this->sql, "UPDATE") === 0) {
                $this->sqlMode = 3;
            } else if (strpos($this->sql, "DELETE") === 0) {
                $this->sqlMode = 4;
            } else if (strpos($this->sql, "CALL") === 0) {
                $this->sqlMode = 5;
            } else if (strpos($this->sql, "REPLACE INTO") === 0) {
                $this->sqlMode = 6;
            }
            return $this;

        } else {
            return $this->sql;
        }

    }

    private function getDB() {

        $config = $GLOBALS['DATA_SOURCES'][$this->dataSourceIndex];
        if ($config) {

            $driver = plus::get_array_value($config, 'DRIVER', 'mysql');
            $host = plus::get_array_value($config, 'HOST');
            $db = plus::get_array_value($config, 'DB');
            $user = plus::get_array_value($config, 'USER');
            $password = plus::get_array_value($config, 'PASSWORD');
            $charset = plus::get_array_value($config, 'CHARSET');
            $persist = plus::get_array_value($config, 'PERSIST', false);
            $dsn = "$driver:host=$host;dbname=$db";

            $options = array();
            if ($driver == 'mysql')
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $charset;
            if ($persist)
                $options[PDO::ATTR_PERSISTENT] = true;

            try {
                $db = new PDO($dsn, $user, $password, $options);
                return $db;
            } catch (PDOException $e) {
                throw new Plus_FreeSQLException($e->getMessage());
            }

        } else {
            throw new Plus_FreeSQLException("找不到指定的数据源：{$this->dataSourceIndex}！");
        }

    }

}