<?php

declare(strict_types=1);

namespace Poggit\Virion\Cmd;

use Ahc\Cli;

final class Resolve extends Cli\Input\Command {
	public function __construct() {
		parent::__construct("resolve", "Resolve dependency virions");
		$this->argument("[dir]", "The plugin/virion using virions");
	}
}
