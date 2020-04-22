<?php
class Plus_JSONController {

    protected $configFile;

    private $app;
    private $controller;
    private $action;

    function __construct($app, $controller, $action) {
        $this->app = $app;
        $this->controller = $controller;
        $this->action = $action;
    }


    public function getValue($key, $defaultValue = null) {

        if (array_key_exists($key, $_GET)) {
            return $_GET[$key];
        }
        return $defaultValue;

    }

    public function getValueInt($key, $defaultValue = 0) {

        if (array_key_exists($key, $_GET)) {
            return intval($_GET[$key]);
        }
        return intval($defaultValue);

    }

    public function postValue($key, $defaultValue = null) {

        if (array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }
        return $defaultValue;

    }

    public function postValueInt($key, $defaultValue = 0) {

        if (array_key_exists($key, $_POST)) {
            return intval($_POST[$key]);
        }
        return intval($defaultValue);

    }

    public function requestValue($key, $defaultValue = null) {

        if (array_key_exists($key, $_REQUEST)) {
            return $_REQUEST[$key];
        }
        return $defaultValue;

    }

    public function requestValueInt($key, $defaultValue = 0) {

        if (array_key_exists($key, $_REQUEST)) {
            return intval($_REQUEST[$key]);
        }
        return intval($defaultValue);

    }

    public function cookieValue($key, $defaultValue = null) {

        if (array_key_exists($key, $_COOKIE)) {
            return $_COOKIE[$key];
        }
        return $defaultValue;

    }

    public function cookieValueInt($key, $defaultValue = 0) {

        if (array_key_exists($key, $_COOKIE)) {
            return intval($_COOKIE[$key]);
        }
        return intval($defaultValue);

    }

    public function setConfigFile($configFile) {
        $this->configFile = $configFile;
    }

    public function getConfig($configFile = null) {

        if ($configFile) {
            $this->$configFile = $configFile;
        }

        if ($this->configFile) {
            return require($this->configFile);
        } else {
            return null;
        }

    }

    public function getApp() {
        return $this->app;
    }

    public function getController() {
        return $this->controller;
    }

    public function getAction() {
        return $this->action;
    }

    public function getURI() {
        return array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : null;
    }

    public function getRequestMethod() {
        return array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : null;
    }

    public function getReferer() {
        return array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : null;
    }

    public function getUserAgent() {
        return array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }

    public function getAcceptLanguage() {
        return array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null;
    }

    public function getAcceptEncoding() {
        return array_key_exists('HTTP_ACCEPT_ENCODING', $_SERVER) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : null;
    }

    public static function getRemoteAddr() {
        return array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : null;
    }

}