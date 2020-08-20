<?php

declare(strict_types=1);

namespace Poggit\Virion\Util;

use Exception;

final class ParseException extends Exception {
	public function __construct(string $path, string $message) {
		parent::__construct("Error parsing $path: $message");
	}

	public static function throw(string $path, string $message) {
		throw new self($path, $message);
	}
}
