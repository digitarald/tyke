<?php

if (!defined('TYKE_DEBUG')) define('TYKE_DEBUG', true);

/*
 * Controller
 */
class Controller
{
	/**
	 * @var         Tyke
	 */
	protected $tyke = null;

	protected $layout = true;

	protected $layoutTemplate = 'views/layout.php';

	public $headers = array();

	public $content = null;

	public function __construct($tyke)
	{
		$this->tyke = $tyke;
	}

	/**
	 * Render function return php rendered in a variable
	 *
	 * @param      string File
	 * @return     Controller Instance
	 */
	public function render($file)
	{
		if (!$this->layout) {
			return $this->renderTemplate($file);
		}

		$this->content = $this->renderTemplate($file);
		return $this->renderTemplate($this->layoutTemplate);
	}

	/**
	 * Open template to render and return php rendered in a variable using ob_start/ob_end_clean
	 *
	 * @param      string Path
	 * @return     string Output
	 */
	private function renderTemplate($tyke_file)
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
	 * @param      boolean Indicates that dispatcher will not wait all process
	 * @return     Controller Instance
	 */
	public function redirect($url, $now = false)
	{
		if (!$now) $this->header('Location: ' . $url);
		else header('Location: ' . $url);
	}

}

/*
 * Application core
 */
class Tyke
{

	static protected $routes = array();

	/**
	 * Add url to routes
	 *
	 * @param      string Regexp rule
	 * @param      string Class
	 * @param      string Method
	 * @param      string HTTP method
	 *
	 * @return     Tyke
	 */
	public static function addRoute($rule, $klass, $klass_method, $http_method = 'get')
	{
		$this->routes[] = array('/^' . str_replace('/', '\/', $rule) . '$/', $klass, $klass_method, $http_method);
	}

	public static function bootstrap()
	{
		try {
			$this->dispatch($_GET['url']);
		} catch (Exception $e) {
			header('HTTP/1.1 500 Internal Server Error');
			if (TYKE_DEBUG) {

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
		<head>
				<title>Error!</title>
		</head>
		<body>
				<h1>Caught exception: <?= $e->getMessage(); ?></h1>
				<h2>File: <?= $e->getFile()?></h2>
				<h2>Line: <?= $e->getLine()?></h2>
				<h3>Trace</h3>
				<pre><?php print_r ($e->getTraceAsString()); ?></pre>
				<h3>Exception Object</h3>
				<pre><?php print_r ($e); ?></pre>
				<h3>Var Dump</h3>
				<pre><?php debug_print_backtrace (); ?></pre>
		</body>
</html><?php

			} else {
				echo 'Oops';
			}
		}
	}

	/**
	 * Process requests and dispatch
	 */
	public static function dispatch($url)
	{
		foreach(self::routes as $rule => $conf) {
			if (preg_match($conf[0], $url, $matches) and strtolower($_SERVER['REQUEST_METHOD']) == strtolower($conf[3])) {
				$matches = $this->parseUrl($matches);//Only declared variables in url regex

				$klass = new $conf[1]();

				ob_start();

				call_user_func_array(array($klass , $conf[2]),$matches);

				$out = ob_get_contents();
				ob_end_clean();

				foreach($klass->headers as $header){
					header($header);
				}

				print $out;
				exit;
			}
		}

		call_user_func_array('r404', $_SERVER['REQUEST_METHOD']);
	}

	/**
	 * Parse url arguments
	 *
	 * @param      array
	 * @return     array
	 */
	private function parseUrl($matches) {
		array_shift($matches);

		$new_matches = array();
		foreach($matches as $k => $match){
			if (is_string($k)) {
				$new_matches[$k] = $match;
			}
		}
		return $new_matches;
	}
}

?>
