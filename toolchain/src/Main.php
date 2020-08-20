<?php

declare(strict_types=1);

namespace Poggit\Virion;

use Ahc\Cli;

final class Main {
	public static function main() : void {
		global $argv;

		$app = new Cli\Application("virion", "0.1.0");
		$app->add(new Cmd\Compile);
		$app->add(new Cmd\Resolve);
		$app->add(new Cmd\Inject);
		$app->add(new Cmd\Build);

		$app->handle($argv);
	}
}
