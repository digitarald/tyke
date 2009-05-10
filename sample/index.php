<?php
error_reporting(E_ALL);
			ini_set('display_errors', '1');
require('../Tyke.php');

// some configuration
Tyke::set('tyke.debug', true);

// Sample controller
class App extends TykeC
{

	public function index()
	{
		$this->name = 'World';

		$this->render('views/greeting.php');
	}

	public function greet($name)
	{
		$this->name = htmlspecialchars(ucfirst($name));

		$this->render('views/greeting.php');
	}

}

Tyke::register('/', array('App'));

Tyke::register('/welcome-(?P<name>[-\w]+)', array('App', 'greet'));

Tyke::run();

?>