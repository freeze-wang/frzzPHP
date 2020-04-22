<?php
class Plus_ReportUtil {

    public static function Query($sql, $params = null, $dataSourceIndex = 0) {
        return plus::_sql($sql, $params, $dataSourceIndex)->_list();
    }

    public static function Get($sql, $params = null, $dataSourceIndex = 0) {
        return plus::_sql($sql, $params, $dataSourceIndex)->_get();
    }

    public static function Execute($sql, $params = null, $dataSourceIndex = 0) {
        return plus::_sql($sql, $params, $dataSourceIndex)->_execute();
    }

    public static function Each($array, $callback) {

        if (!is_array($array) || !is_callable($callback)) return;
        foreach ($array as $item) $callback($item);

    }

    public static function Map($array, $keyList) {

        if (!is_array($array)) return null;
        if (!is_string($keyList) && !is_array($keyList)) return null;
        if (is_string($keyList)) $keyList = explode(',', $keyList);

        $result = array();
        foreach ($array as &$item) {
            $key = '';
            $s = '';
            foreach ($keyList as $arrayKey) {
                $key .= $s . $item[$arrayKey];
                $s = '|';
            }
            $result[$key] = $item;
        }
        return $result;

    }

    public static function Merge(&$targetArray, $targetKeyList, &$srcMap, $callback) {

        if (!is_array($targetArray)) return null;
        if (!is_array($srcMap)) return null;
        if (!is_callable($callback)) return null;
        if (!is_string($targetKeyList) && !is_array($targetKeyList)) return null;
        if (is_string($targetKeyList)) $targetKeyList = explode(',', $targetKeyList);
        foreach ($targetArray as &$targetItem) {
            $key = '';
            $s = '';
            foreach ($targetKeyList as $arrayKey) {
                $key .= $s . $targetItem[$arrayKey];
                $s = '|';
            }
            $srcItem = & $srcMap[$key];
            $callback($targetItem, $srcItem);
        }

    }

    public static function Mix(&$targetArray, $targetKeyList, &$srcMap, $callback, $mixedCallback) {

        if (!is_array($targetArray)) return null;
        if (!is_array($srcMap)) return null;
        if (!is_callable($callback)) return null;
        if (!is_callable($mixedCallback)) return null;
        if (!is_string($targetKeyList) && !is_array($targetKeyList)) return null;
        if (is_string($targetKeyList)) $targetKeyList = explode(',', $targetKeyList);

        $mappedKey = array();
        foreach ($targetArray as &$targetItem) {
            $key = '';
            $s = '';
            foreach ($targetKeyList as $arrayKey) {
                $key .= $s . $targetItem[$arrayKey];
                $s = '|';
            }
            $srcItem = null;
            if (array_key_exists($key, $srcMap)) {
                $mappedKey[] = $key;
                $srcItem = & $srcMap[$key];
            }
            $callback($targetItem, $srcItem);
        }

        foreach ($srcMap as $srcKey => &$srcValue) {
            if (!in_array($srcKey, $mappedKey))
                $mixedCallback($targetArray, $srcValue);
        }

    }


}