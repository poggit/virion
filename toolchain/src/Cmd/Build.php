<?php

declare(strict_types=1);

namespace Poggit\Virion\Cmd;

use Ahc\Cli;

final class Build extends Cli\Input\Command {
	public function __construct() {
		parent::__construct("build", "Build a plugin/virion with dependencies");
		$this->argument("[dir]", "The plugin/virion using virions");
		$this->option("--out", "Path to save output phar");
		$this->option("--tmp", "Sets the temp directory to use");
	}
}
