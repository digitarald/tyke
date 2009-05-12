<?php

require('../Tyke.php');

// debug enables display-errors and exception page
Tyke::set('tyke.debug', true);

// include more libraries, start a session ... feed the dog

// Sample controller
class Sample extends TykeC
{

	public function index()
	{
		$this->forward(array('Sample', 'greet'), array('World'));
	}

	public function greet($name)
	{
		$this->name = htmlspecialchars(ucfirst($name));
		// also available as $_GET['name']

		$this->render('views/greeting.php');
	}

}

Tyke::register(array(
	'/' => array('Sample'), // method defaults to 'index'
	'/welcome-(name:[-\w]+)' => array('Sample', 'greet')
));

// Let the dog out ...
Tyke::run();

?>