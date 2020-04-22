<?php
class Plus_Model {

    //私有变量
    //数据源索引
    protected $dataSourceIndex;
    //数据库表名
    protected $tableName;
    //主键
    protected $primaryKey;
    //数据结构
    protected $scheme;
    //设置要选择的列
    protected $fields;
    //对象值储存数组
    protected $values;
    protected $fieldModified = array();
    protected $sql = '';

    /**
     * 构造函数
     * @param   $tableName 数据表名
     * @param   $dataSourceIndex 数据源索引
     * @param   $schemeDir 数据结构目录
     */
    public function __construct($tableName, $dataSourceIndex = 0, $schemeDir = '') {

        $this->tableName = $tableName;
        $this->dataSourceIndex = $dataSourceIndex;

        if (!$schemeDir) {
            $schemeDir = RUNTIME_DIR . 'database_scheme' . DIRECTORY_SEPARATOR;
        }
        $schemeFile = $schemeDir . strtolower($tableName) . '.php';
        if (file_exists($schemeFile)) {
            $this->scheme = require($schemeFile);
            $this->primaryKey = $this->scheme['PRIMARY_KEY'];
        } else {
            die('Please generate scheme first!');
        }

    }

    /**
     * 设置要选择的列
     * @param $selectedFields
     * @return Plus_Model
     */
    public function Fields($selectedFields) {
        $this->fields = $selectedFields;
        return $this;
    }


    /**
     * 根据指定的条件加载记录到值储存
     * @param  $key 主键值
     */
    public function Load($key) {

        $this->values = null;
        //确定传进来的参数是数组
        if (!is_array($key)) $key = array($key);
        $sql = $this->scheme['LOAD_SQL'];
        if ($sql) {
            //执行查询并把结果加载到值储存
            $result = plus::_sql($sql, $key, $this->dataSourceIndex)->_get();
            if ($result) {
                $this->values = array();
                foreach ($result as $key => $value) {
                    $this->values[$key] = $value;
                }
            }
        }
        return $this->values;

    }


    /**
     * 列出数据表里指定位置的数据
     * @return array
     */
    public function listAll() {

        $result = null;
        $sql = $this->scheme['LIST_SQL'];
        if ($sql) {
            $result = plus::_sql($sql, $this->dataSourceIndex)->_list();
        }
        return $result;

    }

    public function pageListAll($pageNum, $pageSize) {

        $result = null;
        $sql = $this->scheme['LIST_SQL'];
        if ($sql) {
            $result = plus::_sql($sql, $this->dataSourceIndex)->_pagedList($pageNum, $pageSize);
        }
        return $result;

    }

    public function Insert($values = null, $delay = false) {

        if ($values != null) {
            foreach ($values as $key => $value) {
                $this->values[$key] = $value;
                $this->fieldModified[$key] = true;
            }
        }
        //生成插入的SQL语句
        $insertValues = array();
        foreach ($this->values as $key => $value) {
            if (array_key_exists($key, $this->fieldModified) && $this->fieldModified[$key] &&
                array_search($key, $this->scheme['RAW_FIELDS']) !== false
            ) {
                $insertValues[$key] = $value;
            }
        }

        //生成插入的SQL语句
        $paramValues = array();
        //循环生成SQL语句
        $comma = '';
        $fieldList = '';
        $valueList = '';
        foreach ($insertValues as $paramKey => $paramValue) {
            $fieldList .= $comma . $paramKey;
            $valueList .= $comma . "?";
            $comma = ', ';
            $paramValues[] = $paramValue;
        }
        $sql = "INSERT INTO {$this->tableName}($fieldList) VALUES($valueList)";
        $this->fieldModified = array();
        return $this->Execute($sql, $paramValues, $delay);

    }

    public function delayInsert($values = null) {
        return $this->Insert($values, true);
    }

    public function Update($values = null, $delay = false) {

        //检查PrimaryKey是否有赋值
        if (($this->values != null && array_key_exists($this->primaryKey, $this->values))
            || ($values != null && array_key_exists($this->primaryKey, $values))
        ) {

            if ($values != null) {
                foreach ($values as $key => $value) {
                    $this->values[$key] = $value;
                    $this->fieldModified[$key] = true;
                }
            }
            //生成更新SQL语句
            $updateValues = array();
            foreach ($this->values as $key => $value) {
                if ($key != $this->primaryKey &&
                    array_key_exists($key, $this->fieldModified) && $this->fieldModified[$key] &&
                    array_search($key, $this->scheme['RAW_FIELDS']) !== false
                ) {
                    $updateValues[$key] = $value;
                }
            }

            //生成更新的SQL语句
            $sql = "UPDATE {$this->tableName} SET";
            $paramValues = array();
            //循环生成SQL语句
            $comma = '';
            foreach ($updateValues as $paramKey => $paramValue) {
                $sql .= $comma . " {$this->tableName}.{$paramKey} = ?";
                $comma = ', ';
                $paramValues[] = $paramValue;
            }
            $sql .= " WHERE {$this->tableName}.{$this->primaryKey} = ?";
            $paramValues[] = $this->values[$this->primaryKey];
            $this->fieldModified = array();
            return $this->Execute($sql, $paramValues, $delay);

        } else {
            throw new Exception("没有定义主键！");
        }

    }

    public function delayUpdate($values = null) {
        $this->Update($values, true);
    }

    public function Delete($key = null) {

        if ($key) {
            $this->values[$this->primaryKey] = $key;
        }

        //检查PrimaryKey是否有赋值
        if (array_key_exists($this->primaryKey, $this->values)) {
            //生成删除SQL语句
            $sql = $this->scheme['DELETE_SQL'];
            if ($sql) {
                //返回更新影响的行数
                return plus::_sql($sql, $this->values[$this->primaryKey], $this->dataSourceIndex)->_execute();
            }
        }
        return false;

    }

    /**
     * 执行一个SQL语句
     * @param  $sql string
     * @param  $params array
     * @param  $delay bool
     * @return int 影响的行数
     */
    public function Execute($sql, $params = null, $delay = false) {
        if ($delay) {
            $queue = new Plus_JobQueue();
            return $queue->put(new Plus_SQLJob($sql, $params, $this->dataSourceIndex));
        } else {
            return plus::_sql($sql, $params, $this->dataSourceIndex)->_execute();
        }
    }

    public function delayExecute($sql, $params) {
        return $this->Execute($sql, $params, true);
    }

    /**
     * 拦截各种隐式调用
     * @param $method
     * @param $args
     * @return array
     * @throws Exception
     */
    function __call($method, $args) {

        $mapping = $this->scheme['FIELD_MAPPING'];
        if (preg_match("/^getBy/", $method)) {

            $this->values = null;
            $condition = substr($method, 5, strlen($method) - 5);
            $where = $mapping[self::getDBFieldName($condition)] . ' = ?';
            $sql = $this->scheme['LIST_SQL'] . ' WHERE ' . $where;
            $result = plus::_sql($sql, $args, $this->dataSourceIndex)->_get();
            if ($result) {
                $this->values = array();
                foreach ($result as $key => $value) {
                    $this->values[$key] = $value;
                }
            }
            return $result;

        } else if (preg_match("/^findBy/", $method)) {

            $this->values = null;
            $condition = substr($method, 6, strlen($method) - 6);
            $conditions = explode('And', $condition);
            $where = '';
            if (count($conditions) > 1) {
                if (count($conditions) != count($args)) throw new Exception("findBy条件与参数个数不匹配！");
                $and = '';
                for ($i = 0; $i < count($conditions); $i++) {
                    $where .= $and . $mapping[self::getDBFieldName($conditions[$i])] . ' = ?';
                    $and = ' AND ';
                }
            } else {
                $conditions = explode('Or', $condition);
                if (count($conditions) != count($args)) throw new Exception("findBy条件与参数个数不匹配！");
                $or = '';
                for ($i = 0; $i < count($conditions); $i++) {
                    $where .= $or . $mapping[self::getDBFieldName($conditions[$i])] . ' = ?';
                    $or = ' OR ';
                }
            }

            $sql = $this->scheme['LIST_SQL'] . ' WHERE ' . $where;
            $result = plus::_sql($sql, $args, $this->dataSourceIndex)->_get();
            if ($result) {
                $this->values = array();
                foreach ($result as $key => $value) {
                    $this->values[$key] = $value;
                }
            }
            return $result;

        } else if (preg_match("/^listBy/", $method)) {

            $statement = substr($method, 6, strlen($method) - 6);
            $orderField = '';
            $orderPos = stripos($statement, 'OrderBy');
            if ($orderPos > 0) {
                $condition = substr($statement, 0, $orderPos);
                $orderCondition = substr($statement, $orderPos + 7);
                $descPos = strpos($orderCondition, 'Desc');
                if ($descPos > 0) {
                    $orderField = $mapping[self::getDBFieldName(substr($orderCondition, 0, $descPos))];
                    $order = " DESC";
                } else {
                    $orderField = $mapping[self::getDBFieldName($orderCondition)];
                    $order = " ASC";
                }
            } else {
                $condition = $statement;
            }

            $where = '';
            $conditions = explode('And', $condition);
            if (count($conditions) > 1) {

                if (count($conditions) != count($args)) throw new Exception("listBy条件与参数个数不匹配！");
                $and = '';
                for ($i = 0; $i < count($conditions); $i++) {
                    $where .= $and . $mapping[self::getDBFieldName($conditions[$i])] . ' = ?';
                    $and = ' AND ';
                }

            } else {

                $conditions = explode('Or', $condition);

                if (count($conditions) != count($args)) throw new Exception("listBy条件与参数个数不匹配！");
                $or = '';
                for ($i = 0; $i < count($conditions); $i++) {
                    $where .= $or . $mapping[self::getDBFieldName($conditions[$i])] . ' = ?';
                    $or = ' OR ';
                }

            }

            $sql = $this->scheme['LIST_SQL'] . ' WHERE ' . $where;
            if ($orderField) {
                $sql .= " ORDER BY {$orderField} {$order}";
            }
            return plus::_sql($sql, $args, $this->dataSourceIndex)->_list();

        } else if (preg_match("/^pageListBy/", $method)) {

            $statement = substr($method, 10, strlen($method) - 10);
            $orderField = '';
            $orderPos = stripos($statement, 'OrderBy');
            if ($orderPos > 0) {
                $condition = substr($statement, 0, $orderPos);
                $orderCondition = substr($statement, $orderPos + 7);
                $descPos = strpos($orderCondition, 'Desc');
                if ($descPos > 0) {
                    $orderField = $mapping[self::getDBFieldName(substr($orderCondition, 0, $descPos))];
                    $order = " DESC";
                } else {
                    $orderField = $mapping[self::getDBFieldName($orderCondition)];
                    $order = " ASC";
                }
            } else {
                $condition = $statement;
            }

            $conditions = explode('And', $condition);

            $pageNum = array_shift($args);
            $pageSize = array_shift($args);

            if (count($conditions) != count($args)) throw new Exception("pageListBy条件与参数个数不匹配！");
            $where = '';
            $and = '';
            for ($i = 0; $i < count($conditions); $i++) {
                $where .= $and . $mapping[self::getDBFieldName($conditions[$i])] . ' = ?';
                $and = ' AND ';
            }

            $sql = $this->scheme['LIST_SQL'] . ' WHERE ' . $where;
            if ($orderField) {
                $sql .= " ORDER BY {$orderField} {$order}";
            }
            return plus::_sql($sql, $args, $this->dataSourceIndex)->_pagedList($pageNum, $pageSize);

        }
        return false;

    }

    /**
     * 拦截值储存设置
     * @param  $name 属性名
     * @param  $value 属性值
     * @return void
     */
    function __set($name, $value) {
        $this->values[$name] = $value;
        $this->fieldModified[$name] = true;
    }

    /**
     * 拦截值储存获取
     * @param  $name 属性名
     * @return
     */
    function __get($name) {
        return $this->values[$name];
    }

    /**
     * 把查询值当成数组返回
     * @return array
     */
    function toArray() {
        return $this->values;
    }

    /**
     * 把Camel命名方式的名字转换成数据库方式
     * @static
     * @param  $name 输入
     * @return string 输出
     */
    private static function getDBFieldName($name) {

        $result = '';
        $appendix = false;
        for ($i = 0; $i < strlen($name); $i++) {
            $char = $name[$i];
            if ($char >= 'a' && $char <= 'z') {
                $char = chr(ord($char) - 32);
            } else {
                if ($i > 0) $appendix = true;
            }
            if ($appendix) {
                $result .= '_';
                $appendix = false;
            }
            $result .= $char;
        }

        return $result;

    }

}