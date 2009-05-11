<?php
/**
 * Tyke
 *
 * Is a nano web framework for PHP, like web.py for Python, Sinatra or Camping for Ruby.
 *
 * It was forked by Harald Kirschner of Nice Dog, originally by Tiago Bastos.
 *
 * @author       Tiago Bastos
 * @author       Harald Kirschner <harald@digitarald.com>
 * @copyright    2009 Authors
 * @license      MIT License
 */

/*
 * Tyke - Application core
 *
 * Just configuration and magic routing
 */
class Tyke
{

	static public $routes = array();

	static public $config = array();

	public static function set($name, $value = null)
	{
		if (is_array($name)) {
			foreach ($name as $key => &$value) {
				self::setConfig($key, $value);
			}
		} else {
			self::$config[$name] = $value;
		}
	}

	public static function get($name, $default = null)
	{
		if (!isset(self::$config[$name])) return $default;

		return self::$config[$name];
	}


	/**
	 * Add url to routes
	 *
	 * @param      string Regexp pattern
	 * @param      string Class
	 * @param      string Method
	 * @param      array|null Options
	 *
	 * @return     Tyke
	 */
	public static function register($pattern, $function = 'index', $options = array())
	{
		if (is_array($pattern)) {
			foreach ($pattern as $key => $value) {
				if (is_string($key)) $value = array($key, $value);
				call_user_func_array(array('Tyke', 'register'), $value);
			}
		} else {
			$defaults = array(
				'http_method' => null
			);

			$pattern = str_replace('/', '\/', preg_replace('/\\(([a-z][-\w]*):/i', '(?P<$1>', $pattern));

			self::$routes['/^' . $pattern . '$/U'] = array(
				'function' => $function,
				'options' => array_merge($defaults, $options)
			);
		}
	}

	public static function run()
	{
		if (self::get('tyke.debug', true)) {
			error_reporting(E_ALL);
			ini_set('display_errors', '1');
			set_error_handler(array('Tyke', 'rethrow'));
		}

		try {
			self::dispatch();
		} catch (Exception $e) {

			if (!headers_sent()) header('HTTP/1.1 500 Internal Server Error');

			if (self::get('tyke.debug', true)) {

				$fixedTrace = $e->getTrace();

				if (isset($fixedTrace[0]['file']) && !($fixedTrace[0]['file'] == $e->getFile() && $fixedTrace[0]['line'] == $e->getLine())) {
					array_unshift($fixedTrace, array('file' => $e->getFile(), 'line' => $e->getLine()));
				}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
		<head>
				<title>Error!</title>
		</head>
		<body style="font-family: monospace;">
				<h1>Exception: <?php echo get_class($e); ?></h1>
				<h2>Message</h2>
				<p><?= html_entity_decode($e->getMessage()); ?></p>
				<h2>Stack Trace</h2>
				<ol>
					<?php
					foreach($fixedTrace as $trace) {
						echo '<li>';
						if(isset($trace['file'])) {
							echo $trace['file'];
						} else {
							echo "Unknown file";
						}

						if(isset($trace['line'])) {
							echo " (line: " .$trace['line'] .')';
						} else {
							echo "(Unknown line)";
						}
						echo '</li>';
					}
					?>
				</ol>
		</body>
</html><?php

			} else {
				echo 'Internal Server Error';
			}
		}
	}

	public static function rethrow($errno, $errstr, $errfile, $errline, $errcontext)
	{
		$report = error_reporting();
		if ($report && $report & $errno) {
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		}
	}

	/**
	 * Process requests and dispatch
	 */
	public static function dispatch($uri = null)
	{
		if ($uri === null) {
			$parts = array_merge(array('path' => '', 'query' => ''), parse_url('ty://ke' . $_SERVER['REQUEST_URI']));

			$prepend = dirname($_SERVER['SCRIPT_NAME']);

			$from = 0;
			if (strlen($prepend) > 1) $from = strlen($prepend);

			$uri = substr($parts['path'], $from);

			$_GET = array();

			if ($parts['query']) {
				parse_str($parts['query'], $query);
				if (is_array($query)) $_GET = $query;
			}
		}

		Tyke::set('tyke.run.uri', $uri);

		foreach(self::$routes as $pattern => $route) {

			if (!preg_match($pattern, $uri, $matches)) {
				continue;
			}

			$options = $route['options'];

			// optional http_method, can be string or array
			if (!empty($options['http_method'])) {
				$check = $_SERVER['REQUEST_METHOD'];
				if (is_array($options['http_method'])) {
					if (in_array($check, $options['http_method'])) continue;
				} else {
					if ($options['http_method'] == $check) continue;
				}
			}

			Tyke::set('tyke.run.route', $route);

			// get params from named matches
			array_shift($matches);

			$params = array();

			foreach($matches as $key => $match){
				if (is_string($key)) $params[$key] = $match;
			}

			Tyke::set('tyke.run.params', $params);

			// populate data back to global data holders
			$_GET = array_merge($_GET, $params);
			$_REQUEST = array_merge($_POST, $_GET, $_COOKIE);

			self::execute($route['function'], $params);
		}

		$r404 = Tyke::get('tyke.r404', 'r404');

		if (function_exists($r404)) {
			call_user_func($r404, $_SERVER['REQUEST_METHOD']);
		} else {
			header('HTTP/1.1 404 Not Found');
			die('Error: 404 Not Found');
		}
	}

	public function execute($function, array $params = array())
	{
		if (is_string($function) && strpos($function, '::') !== false) $function = explode('::', $function, 2);

		if (is_array($function)) {
			if (is_string($function[0])) {
				if (!class_exists($function[0], true)) throw new Exception('Controller "'.$class.'" Not Found');

				$function[0] = new $function[0]();
			}

			if (empty($function[1])) $function[1] = 'index';

			if (!method_exists($function[0], $function[1]) && !method_exists($function[0], '__call')) {
				throw new Exception('Method "'.$function[1].'" Not Found');
			}
		}

		ob_start();

		call_user_func_array($function, $params);

		$out = ob_get_contents();
		ob_end_clean();

		if (is_array($function)) {
			foreach($function[0]->headers as $header) header($header);
		}

		print $out;
		exit;
	}

}


/*
 * TykeC - The Controller
 */
class TykeC
{
	protected $layout = true;

	protected $layoutTemplate = 'views/layout.php';

	public $headers = array();

	/**
	 * Render function return php rendered in a variable
	 *
	 * @param      string File
	 * @return     Controller Instance
	 */
	public function render($file, $vars = null)
	{
		if (!$this->layout) {
			print $this->renderPartial($file, $vars);
		} else {
			$this->content = $this->renderPartial($file, $vars);

			print $this->renderPartial($this->layoutTemplate, $vars);
		}
		return $this;
	}

	/**
	 * Open template to render and return php rendered in a variable using ob_start/ob_end_clean
	 *
	 * @param      string Path
	 * @return     string Output
	 */
	protected function renderPartial($tyke_file, $tyke_vars = null)
	{
		if (!is_readable($tyke_file)) throw new Exception('View "'.$tyke_file.'" Not Found');

		if (is_array($tyke_vars)) extract($tyke_vars, EXTR_REFS | EXTR_PREFIX_INVALID, '_');

		extract(get_object_vars($this), EXTR_REFS | EXTR_PREFIX_INVALID, '_');

		ob_start();

		require($tyke_file);

		$out = ob_get_contents();
		ob_end_clean();

		return $out;
	}

	/**
	 * Add information in header
	 *
	 * @param      string Value
	 * @return     Controller Instance
	 */
	public function header($text)
	{
		$this->headers[] = $text;
		return $this;
	}

	/**
	 * Redirect to a new page
	 *
	 * @param      string uri
	 * @param      boolean Indicates that dispatcher will not wait all process
	 * @return     Controller Instance
	 */
	public function redirect($uri, $now = false)
	{
		if (!$now) return $this->header('Location: ' . $uri);

		header('Location: ' . $uri);
		exit;
	}

	/**
	 * Forward to a new action
	 *
	 * @param      string uri
	 * @param      boolean Indicates that dispatcher will not wait all process
	 * @return     Controller Instance
	 */
	public function forward($function, $params = array())
	{
		Tyke::execute($function, $params);
		exit;
	}

}

?>
