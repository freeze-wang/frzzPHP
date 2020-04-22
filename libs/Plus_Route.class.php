<?php
class Plus_Route {

    private $app;
    private $controller;
    private $action;

    public function getApp() {
        return $this->app;
    }

    public function getController() {
        return $this->controller;
    }

    public function getAction() {
        return $this->action;
    }

    public static function paseURI($uri) {

        $route = new Plus_Route();
        $uriToParse = strpos($uri, '?') !== false ? strstr($uri, '?', true) : $uri;

        if ($uriToParse == '/') {
            $route->app = 'default';
            $route->controller = 'Index';
            $route->action = 'default';
            return $route;
        }

        $uriToParse = urldecode($uriToParse);
        $uriSegments = explode('/', $uriToParse);
        $segCount = count($uriSegments);
        if ($segCount == 2 || ($segCount == 3 && !$uriSegments[2])) {
            $app = $uriSegments[1];
            if (self::appValid($app)) {
                $route->app = $app;
                $route->controller = self::convertToCamelHump('index');
                $route->action = 'default';
            } else {
                $route->app = 'default';
                $route->controller = self::convertToCamelHump($app);
                $route->action = 'default';
            }
        } else if ($segCount == 3 || ($segCount == 4 && !$uriSegments[3])) {
            $app = $uriSegments[1];
            $controller = $uriSegments[2];
            if (self::appValid($app)) {
                $route->app = $app;
                $route->controller = self::convertToCamelHump($controller);
                $route->action = 'default';
            } else {
                $route->app = 'default';
                $route->controller = self::convertToCamelHump($app);
                $route->action = $controller;
            }
        } else if ($segCount >= 4) {
            $app = $uriSegments[1];
            $controller = $uriSegments[2];
            $action = $uriSegments[3];
            if (self::appValid($app)) {
                $route->app = $app;
                $route->controller = self::convertToCamelHump($controller);
                $route->action = $action;
                if ($segCount > 4) {
                    for ($i = 4; $i < $segCount; $i += 2) {
                        $key = array_key_exists($i, $uriSegments) ? $uriSegments[$i] : null;
                        $value = array_key_exists($i + 1, $uriSegments) ? $uriSegments[$i + 1] : null;
                        if ($key) $_GET[$key] = $value;
                    }
                }
            } else {
                $route->app = 'default';
                $route->controller = self::convertToCamelHump($app);
                $route->action = $controller;
                for ($i = 3; $i < $segCount; $i += 2) {
                    $key = array_key_exists($i, $uriSegments) ? $uriSegments[$i] : null;
                    $value = array_key_exists($i + 1, $uriSegments) ? $uriSegments[$i + 1] : null;
                    if ($key) $_GET[$key] = $value;
                }
            }
        }

        return $route;

    }

    private static function appValid($app) {

        $appDir = sprintf("%s/apps/%s/", ROOT, $app);
        return is_dir($appDir);

    }

    private static function convertToCamelHump($src) {

        $result = '';
        $srcArray = explode('_', $src);
        for ($i = 0; $i < count($srcArray); $i++) {
            $result .= ucfirst($srcArray[$i]);
        }
        return $result;

    }

}