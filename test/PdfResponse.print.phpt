<?php declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$html = file_get_contents(__DIR__ . '/PdfResponse.simple.html');
$response = new \PdfResponse\PdfResponse($html);
$response->mPDF->OpenPrintDialog();

ob_start();
$response->send(
	fakeHttpRequest(),
	fakeHttpResponse()
);

$pdfData = ob_get_clean();

assertValidPDF($pdfData);
savePDF($pdfData, 'PdfResponse.print');
