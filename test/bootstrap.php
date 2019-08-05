<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

\Tester\Environment::setup();

function fakeHttpRequest() {
	return new \Nette\Http\Request(
		new \Nette\Http\UrlScript()
	);
}

function fakeHttpResponse() {
	return new \Nette\Http\Response();
}
