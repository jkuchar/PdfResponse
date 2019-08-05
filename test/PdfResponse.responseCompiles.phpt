<?php declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$response = new \PdfResponse\PdfResponse('');


$presenter = new class implements \Nette\Application\IPresenter {

	public function renderDefault() {
	}

	function run(\Nette\Application\Request $request): \Nette\Application\IResponse
	{
		global $response;
		return $response;
	}
};

\Tester\Assert::same(
	$response,
	$presenter->run(new \Nette\Application\Request(''))
);

