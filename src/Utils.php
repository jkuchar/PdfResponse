<?php declare(strict_types=1);


namespace PdfResponse;


final class Utils
{

	public static function tryCall(?callable $callback, ...$arguments) {
		if (\is_callable($callback)) {
			return $callback(...$arguments);
		}
		return null;
	}

}
