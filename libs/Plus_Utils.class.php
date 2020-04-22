<?php
class Plus_Utils {

    public static function randomString($length) {
        $result = '';
        $characters = "abcdefghijklmnopqrstuvwxyz0123456789";
        $max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[rand(0, $max)];
        }
        return $result;
    }

}