<?php

class Router
{
	private $routes = [];
	private $namedRoutes = [];
	private $basePath = '';
	private $prefix = '';
	private $defaults = [];
	private $matchTypes = [
		'i'  => '[0-9]++',
		'a'  => '[0-9A-Za-z]++',
		'h'  => '[0-9A-Fa-f]++',
		'*'  => '.+?',
		'**' => '.++',
		''   => '[^/]++'
	];

	/**
	 * Set the base path.
	 * Useful if you are running your application from a subdirectory.
     *
     * @param string $basePath
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $basePath;
	}

	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}

	public function setDefaults(Array $defaults)
	{
		$this->defaults = $defaults;
	}

	/**
	 * Add named match types. It uses array_merge so keys can be overwritten.
	 *
	 * @param array $matchTypes The key is the name and the value is the regex.
	 */
	public function addMatchTypes($matchTypes)
	{
		$this->matchTypes = array_merge($this->matchTypes, $matchTypes);
	}

	/**
	 * Map a route to a target
	 *
	 * @param string $method One of 4 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PUT|DELETE)
	 * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
	 * @param mixed $target The target where this route should point to. Can be anything.
	 * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
     * @throws Exception
	 *
	 */
	public function map($route, $target, $method, $name = null)
	{

		$route = $this->basePath . $route;

		$this->routes[] = array($method, $route, $target, $name, $this->defaults);

		if($name) {
			if(isset($this->namedRoutes[$this->prefix.$name])) {
				throw new \Exception("Can not redeclare route '{$name}'");
			} else {
				$this->namedRoutes[$this->prefix.$name] = $route;
			}

		}

		return;
	}

	public function get($route, $target, $name = null)
	{
		$this->map($route, $target, 'GET', $name);
	}

	public function post($route, $target, $name = null)
	{
		$this->map($route, $target, 'POST', $name);
	}

    public function put($route, $target, $name = null)
    {
        $this->map($route, $target, 'PUT', $name);
    }

	/**
	 * Reversed routing
	 *
	 * Generate the URL for a named route. Replace regexes with supplied parameters
	 *
	 * @param string $routeName The name of the route.
	 * @param array @params Associative array of parameters to replace placeholders with.
	 * @return string The URL of the route with named parameters in place.
     * @throws Exception
	 */
	public function generate($routeName, array $params = array())
	{
		// Check if named route exists
		if(!isset($this->namedRoutes[$routeName])) {
			throw new \Exception("Route '{$routeName}' does not exist.");
		}

		// Replace named parameters
		$route = $this->namedRoutes[$routeName];
		$url = $route;
		$paramIterator = 0;

		if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				list($block, $pre, , $param, $optional) = $match;

				if ($pre) {
					$block = substr($block, 1);
				}

				if (isset($params[$param])) {
					$url = str_replace($block, $params[$param], $url);
				} elseif (isset($params[$paramIterator])) {
					$url = str_replace($block, $params[$paramIterator], $url);
				} elseif ($optional) {
					$url = str_replace($pre.$block, '', $url);
				}

				if ($param) {
					$paramIterator++;
				}
			}


		}

		return $url;
	}

	/**
	 * Match a given Request Url against stored routes
	 * @param string $requestUrl
	 * @param string $requestMethod
	 * @return array|boolean Array with route information on success, false on failure (no match).
	 */
	public function match($requestUrl = null, $requestMethod = null)
	{

		$params = array();

		// set Request Url if it isn't passed as parameter
		if ($requestUrl === null) {
			$requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
		}

		// Strip query string (?a=b) from Request Url
		if (false !== strpos($requestUrl, '?')) {
			$requestUrl = strstr($requestUrl, '?', true);
		}

		if (strlen($requestUrl) > 1 and strrpos($requestUrl, '/') === (strlen($requestUrl) -1))
		{
			$requestUrl = substr($requestUrl, 0, -1);
		}

		// set Request Method if it isn't passed as a parameter
		if ($requestMethod === null) {
			$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
		}

		// Force request_order to be GETPOST
		$_REQUEST = array_merge($_GET, $_POST);

		foreach($this->routes as $handler) {
			list($method, $_route, $target, $name, $defaults) = $handler;

			$methods = explode('|', $method);
			$method_match = false;

			// Check if request method matches. If not, abandon early. (CHEAP)
			//首先验证http Method 
			foreach($methods as $method) {
				if (strcasecmp($requestMethod, $method) === 0) {
					$method_match = true;
					break;
				}
			}

			// Method did not match, continue to next route.
			if (!$method_match) continue;

			// Check for a wildcard (matches all)
			if ($_route === '*')
			{
				$match = true;
			}
			elseif (isset($_route[0]) && $_route[0] === '@')
			{
				$match = preg_match('`' . substr($_route, 1) . '`', $requestUrl, $params);
			}
			else
			{
				$route = null;
				$regex = false;
				$j = 0;
				$n = isset($_route[0]) ? $_route[0] : null;
				$i = 0;

				// Find the longest non-regex substring and match it against the URI
				while (true)
				{
					if (!isset($_route[$i]))
					{
						break;
					}
					elseif (false === $regex)
					{
						$c = $n;
						$regex = $c === '[' || $c === '(' || $c === '.';

						if (false === $regex && false !== isset($_route[$i+1]))
						{
							$n = $_route[$i + 1];
							$regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
						}

						if (false === $regex && $c !== '/' && (!isset($requestUrl[$j]) || $c !== $requestUrl[$j]))
						{
							continue 2;
						}
						$j++;
					}
					$route .= $_route[$i++];
				}

				$regex = $this->compileRoute($route);
				$match = preg_match($regex, $requestUrl, $params);
			}

			if (($match == true || $match > 0))
			{
				if ($params)
				{
					foreach($params as $key => $value)
					{
						if (is_numeric($key)) unset($params[$key]);
					}
				}

				return array(
					'target' => $target,
					'params' => $params,
					'name' => $name,
					'defaults' => $defaults
				);
			}

		}

		return false;
	}

	public function routeExists($name)
	{
		return isset($this->namedRoutes[$name]);
	}

	/**
	 * Compile the regex for a given route (EXPENSIVE)
     *
     * @param string $route
     * @return string
	 */
	private function compileRoute($route)
	{
		if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER))
		{
			$match_types = $this->matchTypes;

			foreach ($matches as $match)
			{
				$slash = '';

				//var_dump($match); die;
				list($block, $pre, $type, $param, $optional) = $match;

				if (isset($match_types[$type]))
				{
					$type = $match_types[$type];
				}

				if ($pre === '.')
				{
					$pre = '\.';
				}

				if ($pre === '/' and strpos($block, $route) === 0)
				{
					$pre = '';
					$slash = '/';
				}

				$pattern = ($slash !== '' ? $slash : null)
						 . '(?:'
						 . ($pre !== '' ? $pre : null)
						 . '('
						 . ($param !== '' ? '?\''.$param.'\'' : null)
						 . $type
						 . '))'
						 . ($optional !== '' ? '?' : null);

				$route = str_replace($block, $pattern, $route);
			}

		}

		return "`^$route$`";
	}
}
