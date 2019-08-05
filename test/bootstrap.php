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


function assertValidPDF(string $data): void {
	\Tester\Assert::true(
		strpos($data, '%PDF') === 0,
		'Have not found valid PDF file in generated output.'
	);
}

function savePDF(string $data, string $name): void {
	file_put_contents($name . '.output.pdf', $data);
}
