<?php

class Plus_Authority {

    public function getMenu() {

        $menus = plus::_sql("SELECT * FROM authorities WHERE PARENT_ID = 0 ORDER BY ID")->_list();
        foreach ($menus as &$menu) {
            $subMenus = plus::_sql("SELECT * FROM authorities WHERE PARENT_ID = ? ORDER BY ID", $menu['ID'])->_list();
            $menu['SUB_MENUS'] = $subMenus;
        }
        return $menus;

    }

    public static function GetAuthorities($authorities) {
        if (!$authorities) return array();
        return explode(',', $authorities);
    }

    public static function GetAuthorityAttatchment($attatchment) {
        if (!$attatchment) return array();
        return explode(',', $attatchment);
    }

    public static function InsertFirstElement(&$array, $element) {
        array_unshift($array, $element);
    }

    public static function FilterInArray($src, $field, $filter) {

        $result = array();
        foreach ($src as &$item) {
            if (in_array($item[$field], $filter)) {
                $result[] = $item;
            }
        }
        return $result;

    }

    public static function GetFirstElement($array) {
        if (count($array) == 0) return null;
        return array_shift($array);
    }

    public static function GetInStatement(&$array, $field, $isString = false) {
        $statement = '(';
        $delimiter = '';
        $quote = $isString ? '\'' : '';
        foreach ($array as $item) {
            $statement .= ' ' . $delimiter . $quote . $item[$field] . $quote;
            $delimiter = ',';
        }
        $statement .= ')';
        return $statement;
    }

}