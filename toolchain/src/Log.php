<?php

declare(strict_types=1);

namespace Poggit\Virion;

use Ahc\Cli\Output\Writer;
use const PHP_EOL;

final class Log {
	public static function fatal(string $message) : void {
		$writer = new Writer;
		$writer->errorBold("[FATAL] ");
		$writer->error($message);
		$writer->error(PHP_EOL);
		exit(1);
	}

	public static function debug(string $message) : void {
		$writer = new Writer;
		$writer->commentBold("[DEBUG] ");
		$writer->comment($message);
		$writer->comment(PHP_EOL);
	}
}
