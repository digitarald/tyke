<?php

require('../Tyke.php');

// some configuration
Tyke::set('tyke.debug', true);

// Sample controller
class App extends TykeC
{

	public function index($params)
	{
		$this->name = 'World';

		$this->render('views/greeting.php');
	}

	public function greet($params)
	{
		$this->name = htmlspecialchars(ucfirst($params['name']));

		$this->render('views/greeting.php');
	}

}

Tyke::register('/', 'App');

Tyke::register('/welcome-(?P<name>[-\w]+)', 'App', 'greet');

Tyke::bootstrap();

?>