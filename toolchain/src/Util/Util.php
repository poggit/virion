<?php

declare(strict_types=1);

namespace Poggit\Virion;

use const DIRECTORY_SEPARATOR;
use function rtrim;
use function strlen;

final class Util {
	public static function cleanPath(string $path) : string {
		if($path === "/" || $path{1} === ":" && strlen($path) === 3) {
			return $path;
		}
		return rtrim($path, "/" . DIRECTORY_SEPARATOR);
	}
}
