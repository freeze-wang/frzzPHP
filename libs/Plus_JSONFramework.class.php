<?php
class Plus_JSONFramework {

    const APP_NOT_FOUND = 'APP_NOT_FOUND';
    const ILLEGAL_APP = 'ILLEGAL_APP';
    const CONTROLLER_NOT_FOUND = 'CONTROLLER_NOT_FOUND';
    const ILLEGAL_CONTROLLER = 'ILLEGAL_CONTROLLER';
    const ACTION_NOT_FOUND = 'ACTION_NOT_FOUND';
    const ILLEGAL_ACTION = 'ILLEGAL_ACTION';
    const BEFORE_LOAD_CLASS = 'BEFORE_LOAD_CLASS';
    const CLASS_LOADED = 'CLASS_LOADED';
    const BEFORE_ACTION = 'BEFORE_ACTION';
    const AFTER_ACTION = 'AFTER_ACTION';
    const VALID_PARAM = '/^[a-zA-Z][a-zA-Z0-9_]{0,255}$/';

    private $callBacks = array();

    public function registerCallBack($key, $callback = null) {

        if (is_array($key)) {
            foreach ($key as $cbKey => $cbValue) {
                $this->callBacks[$cbKey] = $cbValue;
            }
        } else if (is_string($key) && $callback) {
            $this->callBacks[$key] = $callback;
        }
        return $this;

    }

    public function execute() {

        $route = Plus_Route::paseURI($_SERVER['REQUEST_URI']);
        if (!preg_match(self::VALID_PARAM, $route->getApp())) {
            throw new Plus_JSONFrameworkException(self::ILLEGAL_APP);
        }
        if (!preg_match(self::VALID_PARAM, $route->getController())) {
            throw new Plus_JSONFrameworkException(self::ILLEGAL_CONTROLLER);
        }
        if (!preg_match(self::VALID_PARAM, $route->getAction())) {
            throw new Plus_JSONFrameworkException(self::ILLEGAL_ACTION);
        }

        $controllerFile = sprintf("%sapps/%s/%s.json.php", ROOT, $route->getApp(), $route->getController());
        if (file_exists($controllerFile)) {

            if ($this->handleFrameworkCallBack(self::BEFORE_LOAD_CLASS, $route)) {

                //包含Controller文件
                /** @noinspection PhpIncludeInspection */
                include($controllerFile);

                $controllerName = $route->getController();
                $controller = new $controllerName($route->getApp(), $route->getController(), $route->getAction());
                if ($controller &&
                    is_subclass_of($controller, "Plus_JSONController")
                ) {

                    $this->handleFrameworkCallBack(self::CLASS_LOADED, $route);

                    $configFile = sprintf("apps/%s/config/%s.config.php", $route->getApp(), $route->getController());
                    if (file_exists(ROOT . $configFile)) /** @noinspection PhpUndefinedMethodInspection */
                    $controller->setConfigFile($configFile);

                    $action = '_' . $route->getAction();
                    if (is_callable(array(&$controller, $action), false)) {
                        if ($this->handleFrameworkCallBack(self::BEFORE_ACTION, $route)) {
                            $jsonContent = file_get_contents("php://input");
//                            plus::debug($jsonContent);
                            $jsonRequest = json_decode($jsonContent, true);
                            //执行 controller 下的 action
                            $jsonResponse = $controller->$action($jsonRequest);
                            echo json_encode($jsonResponse);

                            $this->handleFrameworkCallBack(self::AFTER_ACTION, $route);
                        }
                    } else {
                        $this->handleFrameworkError(0, self::ACTION_NOT_FOUND, $route);
                    }

                } else {
                    $this->handleFrameworkError(0, self::ILLEGAL_CONTROLLER, $route);
                }

            }
        } else {
            $this->handleFrameworkError(0, self::CONTROLLER_NOT_FOUND, $route);
        }

    }


    /**
     * @param $key string
     * @param $route Plus_Route
     * @return bool
     */
    private function handleFrameworkCallBack($key, $route) {

        if (array_key_exists($key, $this->callBacks)) {
            $function = $this->callBacks[$key];
            if (is_string($function)) {
                return $function($route->getApp(), $route->getController(), $route->getAction());
            } else if (is_array($function)) {
                list($obj, $method) = $function;
                if ($obj && $method) {
                    return $obj->$method($route->getApp(), $route->getController(), $route->getAction());
                }
            }
        }
        return true;

    }

    /**
     * @param $errorCode int
     * @param $errorType string
     * @param $route Plus_Route
     * @throws Plus_JSONFrameworkException
     */
    private function handleFrameworkError($errorCode, $errorType, $route) {

        if (array_key_exists($errorType, $this->callBacks)) {
            $function = $this->callBacks[$errorType];
            if (is_string($function)) {
                $function($route->getApp(), $route->getController(), $route->getAction());
            } else if (is_array($function)) {
                list($obj, $method) = $function;
                if ($obj && $method) {
                    $obj->$method($route->getApp(), $route->getController(), $route->getAction());
                }
            }
        } else {
            throw new Plus_JSONFrameworkException($errorType, $errorCode);
        }

    }

}