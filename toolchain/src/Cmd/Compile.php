<?php

declare(strict_types=1);

namespace Poggit\Virion\Cmd;

use function file_exists;
use function is_dir;
use function is_file;
use function mkdir;
use function sprintf;
use function str_replace;
use Ahc\Cli;
use Poggit\Virion\Log;
use Poggit\Virion\Util\Util;

final class Compile extends Cli\Input\Command {
	public function __construct() {
		parent::__construct("compile", "Compile virion into a distributable phar");
		$this->argument("[dir]", "The virion directory");
		$this->option("--out", "Path to save output phar");
		$this->option("--tmp", "Sets the temp directory to use");
	}

	public function execute(?string $dir, ?string $out, ?string $tmp) : void {
		$writer = new Cli\Output\Writer;

		$dir = Util::cleanPath($dir ?? ".");
		if(!is_dir($dir)) {
			Log::fatal("$dir is not a directory");
		}

		if(!is_file("$dir/virion.yml")) {
			Log::fatal("$dir/virion.yml does not contain virion.yml");
		}

		try {
			$virionYml = VirionSourceYml::read("$dir/virion.yml");
			$virionYml->validate();
		} catch(ParseException $e) {
			Log::fatal($e->getMessage());
		}

		$psr0Dir = sprintf("%s/src/%s", $dir, str_replace("\\", "/", $antigen));
		if(is_dir($psr0Dir)) {
			$psr0 = true;
			$src = Util::cleanPath($psr0Dir);
		} else {
			$psr0 = false;
			$src = "$dir/src";
			if(!is_dir($src)) {
				Log::fatal("$src is not a directory");
			}
		}

		$out = "{$virionYml->name}.phar";
		if(file_exists($out)) {
			$confirm = (new Cli\IO\Interactor)->confirm("$out already exists. Overwrite?");
			if(!$confirm) {
				Log::fatal("Operation cancelled by user");
			}
		}

		for($i = 0; ; $i++) {
			$tmp = ".$out.$i.tmp";
			if(mkdir($tmp)) {
				break;
			}
		}

		Log::debug("Compiling $dir to $out with temp directory $tmp");
	}
}
