<?php
class Plus_TimeStamp {

    public static function NOW() {
        return date('Y-m-d H:i:s');
    }

    public static function Format($format, $time) {
        return date($format, strtotime($time));
    }

}