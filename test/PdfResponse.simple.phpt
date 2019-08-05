<?php declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$html = file_get_contents(__DIR__ . '/PdfResponse.simple.html');
$response = new \PdfResponse\PdfResponse($html);

ob_start();
$response->send(
	fakeHttpRequest(),
	fakeHttpResponse()
);
$pdfData = ob_get_clean();
\Tester\Assert::true(
	strpos($pdfData, '%PDF-1.4') === 0,
	'Have not found valid PDF file in generated output.'
);
file_put_contents('PdfResponse.simple.output.pdf', $pdfData);
