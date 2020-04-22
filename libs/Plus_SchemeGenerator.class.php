<?php
class Plus_SchemeGenerator {

    private $schemeDir;
    private $modelDir;
    private $dataSourceIndex;
    private $dbName;

    public function __construct($dataSourceIndex = 0, $schemeDir = '') {

        if (!$schemeDir) {
            $schemeDir = RUNTIME_DIR . 'database_scheme' . DIRECTORY_SEPARATOR;
        }
        $this->schemeDir = $schemeDir;
        if (!file_exists($this->schemeDir)) mkdir($this->schemeDir, 0755);

        $this->modelDir = ROOT . 'models' . DIRECTORY_SEPARATOR;
        if (!file_exists($this->modelDir)) mkdir($this->modelDir, 0755);

        $this->dataSourceIndex = $dataSourceIndex;
        $config = $GLOBALS['DATA_SOURCES'][$this->dataSourceIndex];
        if ($config) {
            $this->dbName = $config['DB'];
        }

    }

    public function dumpScheme() {

        $tables = plus::_sql("SELECT TABLE_NAME,TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", $this->dbName, $this->dataSourceIndex)->_list();
        foreach ($tables as $table) {

            $tableName = $table['TABLE_NAME'];

            //获取Model名字
            $modelName = '';
            if (preg_match('@\[MODEL:([\w]+)\]@', $table['TABLE_COMMENT'], $matches)) {
                $modelName = $matches[1];
            }
            //获取缓存类型
            $cacheType = 'NO_CACHE';
            if (preg_match('@\[CACHE_TYPE:([\w]+)\]@', $table['TABLE_COMMENT'], $matches)) {
                $cacheType = $matches[1];
            }

            $sql = "SELECT COLUMN_NAME,DATA_TYPE,COLUMN_KEY,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
            $fieldInfoList = plus::_sql($sql, array($this->dbName, $tableName), $this->dataSourceIndex)->_list();
            $rawFieldList = array();
            $fieldMapping = array();
            $fieldNameList = array();
            $primaryKey = null;
            $primaryKeyName = null;
            foreach ($fieldInfoList as $fieldInfo) {
                $rawFieldList[] = $fieldInfo['COLUMN_NAME'];
                $fieldNameList[] = $tableName . '.' . $fieldInfo['COLUMN_NAME'] . ' AS ' . $fieldInfo['COLUMN_NAME'];
                $fieldMapping[$fieldInfo['COLUMN_NAME']] = $tableName . '.' . $fieldInfo['COLUMN_NAME'];
                if ($fieldInfo['COLUMN_KEY'] === 'PRI') {
                    $primaryKey = $fieldInfo['COLUMN_NAME'];
                    $primaryKeyName = $tableName . '.' . $fieldInfo['COLUMN_NAME'];
                }
            }

            $joint = '';
            $sql = "SELECT TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_SCHEMA,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL";
            $foreignFieldInfoList = plus::_sql($sql, array($this->dbName, $tableName), $this->dataSourceIndex)->_list();
            if (count($foreignFieldInfoList) > 0) {
                foreach ($foreignFieldInfoList as $foreignFieldInfo) {
                    $thisFieldName = $foreignFieldInfo['COLUMN_NAME'];
                    $foreignDb = $foreignFieldInfo['REFERENCED_TABLE_SCHEMA'];
                    $foreignTable = $foreignFieldInfo['REFERENCED_TABLE_NAME'];
                    $foreignFieldName = $foreignFieldInfo['REFERENCED_COLUMN_NAME'];
                    $foreignFieldPrefix = ($foreignFieldName === $thisFieldName) ? $thisFieldName . '_' : str_replace($foreignFieldName, '', $thisFieldName);
                    $sql = "SELECT COLUMN_NAME,DATA_TYPE,COLUMN_KEY,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
                    $fieldInfoList = plus::_sql($sql, array($foreignDb, $foreignTable), $this->dataSourceIndex)->_list();
                    foreach ($fieldInfoList as $fieldInfo) {
                        if ($fieldInfo['COLUMN_NAME'] != $foreignFieldName) {
                            $fieldNameList[] = $foreignTable . '.' . $fieldInfo['COLUMN_NAME'] . ' AS ' . $foreignFieldPrefix . $fieldInfo['COLUMN_NAME'];
                            $fieldMapping[$foreignFieldPrefix . $fieldInfo['COLUMN_NAME']] = $foreignTable . '.' . $fieldInfo['COLUMN_NAME'];
                        }
                    }
                    $joint .= "INNER JOIN $foreignTable ON $tableName.$thisFieldName = $foreignTable.$foreignFieldName ";
                }
            }
            $fieldList = implode(',', $fieldNameList);

            $config = array(
                'CACHE_TYPE'    => $cacheType,
                'PRIMARY_KEY'   => $primaryKey,
                'RAW_FIELDS'    => $rawFieldList,
                'FIELD_MAPPING' => $fieldMapping,
                'LOAD_SQL'      => "SELECT {$fieldList} FROM {$tableName} {$joint}WHERE {$primaryKeyName} = ?",
                'LIST_SQL'      => "SELECT {$fieldList} FROM {$tableName} $joint",
                'COUNT_SQL'     => "SELECT COUNT(*) FROM {$tableName} $joint",
                'DELETE_SQL'    => "DELETE FROM {$tableName} WHERE {$primaryKeyName} = ?",
            );

            $configContent = var_export($config, true);
            $php = "<?php return $configContent;";
            $schemeFileName = $this->schemeDir . strtolower($tableName) . '.php';
            file_put_contents($schemeFileName, $php, LOCK_EX);

            if ($modelName) {
                $modelFileName = sprintf("%s%s.class.php", $this->modelDir, $modelName);
                if ($this->dataSourceIndex) {
                    $modelContent = "<?php\r\nclass $modelName extends Plus_Model {\r\n\r\n	public function __construct() {\r\n\t\tparent::__construct(\"$tableName\", {$this->dataSourceIndex});\r\n\t}\r\n\r\n}";
                } else {
                    $modelContent = "<?php\r\nclass $modelName extends Plus_Model {\r\n\r\n	public function __construct() {\r\n\t\tparent::__construct(\"$tableName\");\r\n\t}\r\n\r\n}";
                }
                file_put_contents($modelFileName, $modelContent, LOCK_EX);
            }
        }

        echo "Done!";

    }

}