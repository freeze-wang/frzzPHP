<?php
if(!function_exists("frzzStart")){
function frzzStart(){
    // If behind HAProxy, $_SERVER['HTTPS'] will not get set, this is a quick workaround.
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $_SERVER['HTTPS'] = 'on';
    }

    // Route fix when running via CLI
    if (!isset($_SERVER['REQUEST_URI'])) {
        $_SERVER['REQUEST_URI'] = '/';
    } 

    // URI
    // ------------------------------------------------------
    $uri = $_SERVER['REQUEST_URI'];
    $uri = preg_replace('/(\/+)/', '/', $uri);

    // Remove query string from URI
    if (strpos($uri, '?') !== false) {
        $uri = substr($uri, 0, strrpos($uri, '?'));
    }

    $router = new Router;
    $router->get('/', ['c' => 'start', 'a' => 'getIndex'], 'home');
    //$router->get('/start', ['c' => 'start', 'a' => 'getStart'], 'start');
    $router->map('/start', ['c' => 'start', 'a' => 'getStart'], 'GET|POST', 'start');
    
    // Add custom match types
    $router->addMatchTypes(array(
        'lang' => '[a-z]{2}',						// 2 character language code
        'date_old' => '[0-9]{4}\/[0-9]{2}\/[0-9]{2}',	// Date format: YYYY-MM-DD
        'date' => '[0-9]{4}\-[0-9]{2}\-[0-9]{2}',	// Date format: YYYY-MM-DD
        'pager' => '(?:\/page\/)([0-9]+)'			// Pagination
    ));
    try {
        // Make the router globally available
        //Globals::set('router', $router);

        $match = $router->match($uri);
        if($match){
                // Extract information from route
                $controller = $match['target']['c'] . 'Controller';
                $action = $match['target']['a'];
                $params = array_merge($params??[], $match['defaults'], $match['params']);
                $name = $match['name'];
        }else{
            return "Incorrect request routing!";
        }
        if($match){
            // Will throw a ReflectionException in the event that either the
            // controller or action is not defined
            $refController = new ReflectionClass($controller);
            $refAction = $refController->getMethod($action);

            $c = $refController->newInstanceArgs([$name]);
            return $refAction->invoke($c);
        }
    }catch (\UnavailableException $ex) {
        echo "UnavailableException";
        http_response_code(503);

        // Since ErrorContoller is translated it gets a language ID from the database, but if we couldn't connect to the database that in turn doesn't work.
        if (defined('DEBUG') && DEBUG == true) {
            echo $ex->getMessage() . "<br><br>\n" . nl2br($ex->getTraceAsString());
        } else {
            // TODO: Make better
            // echo "Down for maintenance, back soon!";
            echo file_get_contents('maintenance.html');
        }
    } catch (\Throwable $ex) {
        echo "Throwable";    
        if (defined('DEBUG') && DEBUG == true) {
            echo $ex->getMessage() . "<br><br>\n" . nl2br($ex->getTraceAsString());
        } else {
            $errorCode = $ex->getCode();
            // Show error
            $c = new ErrorController();

            if ($errorCode === 404) {
                // Page not found
                http_response_code(404);
                $c->notFound();
            } else {
                // Server error
                echo $errorCode;
                http_response_code(500);
                $msg = $ex->getMessage() . "\r\n\r\n" . $ex->getTraceAsString();
                error_log($msg);
                $c->serverError();
            }
        }
    }
    return "Something wrong!";
  }
}
?>