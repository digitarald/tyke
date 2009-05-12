<?php

require('../../Tyke.php');

// debug enables display-errors and exception page
Tyke::set('tyke.debug', true);

// include more libraries, start a session ... feed the dog

class Form extends TykeC
{
	protected $errors = array();

	public function index()
	{
		$this->render('views/form.php');
	}

	public function save()
	{
		if (empty($_POST['text'])) {
			$this->errors[] = 'No Text';
		} else {
			$this->md5 = md5($_POST['text']);
		}

		// ajax request
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {

			if (!empty($this->errors)) {
				$result = array('errors' => $this->errors);
			} else {
				$result = array('result' => $this->md5);
			}

			echo json_encode($result);
		} else {
			$this->forward(array($this, 'index'));
		}
	}

}

Tyke::register('/', array('Form'), array('http_method' => 'GET'));
Tyke::register('/', array('Form', 'save'), array('http_method' => 'POST'));

// Let the dog out ...
Tyke::run();

?>