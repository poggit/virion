<?php

declare(strict_types=1);

namespace Poggit\Virion\Cmd;

use Ahc\Cli;

final class Inject extends Cli\Input\Command {
	public function __construct() {
		parent::__construct("inject", "Inject a virion into a consumer");
		$this->argument("<consumer>", "The consumer phar");
		$this->argument("<virion>", "The virion phar");
		$this->option("--tmp", "Sets the temp directory to use");
	}
}
