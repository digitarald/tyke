<?php

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
	public function render($file)
	{
		if (!$this->layout) {
			print $this->renderPartial($file);
		} else {
			$this->content = $this->renderPartial($file);

			print $this->renderPartial($this->layoutTemplate);
		}
	}

	/**
	 * Open template to render and return php rendered in a variable using ob_start/ob_end_clean
	 *
	 * @param      string Path
	 * @return     string Output
	 */
	private function renderPartial($tyke_file)
	{
		if (!is_readable($tyke_file)) {
			throw new Exception('View "'.$tyke_file.'" Not Found');
		}

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
	 * @param      string url
	 * @param      boolean Indicates that dispatcher will not wait all process
	 * @return     Controller Instance
	 */
	public function redirect($url, $now = false)
	{
		if (!$now) return $this->header('Location: ' . $url);
		header('Location: ' . $url);
		exit;
	}

}

/*
 * Application core
 */
class Tyke
{

	static protected $routes = array();

	static public $config = array();

	public static function set($name, $value = null)
	{
		if (is_array($name)) {
			foreach ($name as $key => $value) {
				self::setConfig($key, $value);
			}
		} else {
			self::$config[$name] = $value;
		}
	}

	public static function get($name)
	{
		if (!isset(self::$config[$name])) return null;

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
	public static function register($pattern, $class, $method = 'index', $options = array())
	{
		$defaults = array(
			'http_method' => null
		);

		self::$routes[] = array(
			'pattern' => '/^' . str_replace('/', '\/', $pattern) . '$/',
			'class' => $class,
			'method' => $method,
			'options' => array_merge($defaults, $options)
		);
	}

	public static function bootstrap()
	{
		if (self::get('tyke.debug')) {
			error_reporting(E_ALL);
			ini_set('display_errors', '1');
			set_error_handler(array('Tyke', 'rethrow'));
		}

		try {
			self::dispatch();
		} catch (Exception $e) {

			if (!headers_sent()) {
				header('HTTP/1.1 500 Internal Server Error');
			}

			if (self::get('tyke.debug')) {

				// fix stack trace in case it doesn't contain the exception origin as the first entry
				$fixedTrace = $e->getTrace();
				if(isset($fixedTrace[0]['file']) && !($fixedTrace[0]['file'] == $e->getFile() && $fixedTrace[0]['line'] == $e->getLine())) {
					$fixedTrace = array_merge(array(array('file' => $e->getFile(), 'line' => $e->getLine())), $fixedTrace);
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
					foreach($fixedTrace as $no => $trace) {
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
	public static function dispatch($url = null, array $params = array())
	{
		if ($url === null) {
			$parts = array_merge(array('path' => '', 'query' => ''), parse_url('ty://ke' . $_SERVER['REQUEST_URI']));

			$prepend = dirname($_SERVER['SCRIPT_NAME']);

			$from = 0;
			if (strlen($prepend) > 1) $from = strlen($prepend);

			$url = substr($parts['path'], $from);

			if ($parts['query']) {
				parse_str($parts['query'], $query);
				if (is_array($query)) $params = array_merge($query, $params);
			}
		}

		foreach(self::$routes as $route) {

			if (!preg_match($route['pattern'], $url, $matches)) {
				continue;
			}

			$options = $route['options'];

			if (!empty($options['http_method'])) {
				$check = $_SERVER['REQUEST_METHOD'];
				if (is_array($options['http_method'])) {
					if (in_array($check, $options['http_method'])) continue;
				} else {
					if ($options['http_method'] == $check) continue;
				}
			}

			array_shift($matches);

			foreach($matches as $key => $match){
				if (is_string($key)) $params[$key] = $match;
			}

			self::execute($route['class'], $route['method'], $params);
		}

		if (function_exists('r404')) {
			call_user_func('r404', $_SERVER['REQUEST_METHOD']);
		} else {
			header('HTTP/1.1 404 Not Found');
			die('Error: 404 Not Found');
		}
	}

	protected function execute($class, $method, array $params = array())
	{
		$instance = new $class();

		ob_start();

		call_user_func(array($instance, $method), $params);

		$out = ob_get_contents();
		ob_end_clean();

		foreach($instance->headers as $header){
			header($header);
		}

		print $out;
		exit;
	}

}

?>
