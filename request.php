<?php


//ini_set('display_errors', 1);
	require 'vendor/autoload.php';
	require 'Handlers/SimpleIntentRequestHandler.php';
	require 'alexa.class.php';

	/**
	 * Simple example for request handling workflow with help example
	 * loading json
	 * creating request
	 * validating request
	 * adding request handler to registry
	 * handling request
	 * returning json response
	 */
	$requestBody = file_get_contents('php://input');
	if ($requestBody) {
		$alexa = new alexa($requestBody);
		$alexa->handle();
	}

	exit();