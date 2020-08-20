<?php

declare(strict_types=1);

namespace Poggit\Virion\Util;

use Composer\Semver;
use ErrorException;
use function file_get_contents;
use function preg_match;
use function yaml_emit;
use function yaml_parse;

final class VirionSourceYml {
	/** @var string */
	private $path;

	/** @var string */
	public $name;
	/** @var string */
	public $antigen;
	/** @var string */
	public $version;
	/** @var string */
	public $description = "";
	/** @var string[] */
	public $authors = [];
	/** @var string[] */
	public $php = null;
	/** @var string[] */
	public $api = null;
	/** @var string|null */
	public $sharable = null;

	public static function read(string $path) : self {
		$contents = file_get_contents($path);
		try {
			$data = yaml_parse($contents);
		} catch (ErrorException $e) {
			throw new ParseException($path, $e->getMessage());
		}
		$instance = new self;
		$instance->path = $path;
		$instance->name = $data["name"] ?? ParseException::throw($path, "missing required attribute \"name\"");
		$instance->antigen = $data["antigen"] ?? ParseException::throw($path, "missing required attribute \"antigen\"");
		$instance->version = $data["version"] ?? ParseException::throw($path, "missing required attribute \"version\"");
		$instance->description = $data["description"] ?? "";
		$instance->authors = $data["authors"] ?? [];
		$instance->php = $data["php"] ?? null;
		$instance->api = $data["api"] ?? null;
		$instance->sharable = $data["sharable"] ?? null;
		return $instance;
	}

	public function validate() : void {
		if(!preg_match('/^[A-Za-z0-9\-_]+$/', $this->name)) {
			throw new ParseException($path, "attribute \"name\" may only contain A-Z, a-z, 0-9, - and _");
		}
		if(!preg_match('/^([A-Za-z_][A-Za-z0-9_]*\\\\)*A[Za-z_][A-Za-z0-9_]*\\\\?$/', $this->antigen)) {
			throw new ParseException($path, "attribute \"antigen\" must be a namespace");
		}
		if(!preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$/', $this->version)) {
			throw new ParseException($path, "attribute \"version\" must be a valid semantic version");
		}
		if($this->php === null && $this->api === null) {
			throw new ParseException($path, "at least one of \"php\" or \"api\" must be provided");
		}
		if($this->sharable !== null && !preg_match('/^([A-Za-z_][A-Za-z0-9_]*\\\\)*A[Za-z_][A-Za-z0-9_]*\\\\?$/', $this->sharable)) {
			throw new ParseException($path, "attribute \"sharable\" must be a namespace");
		}
	}

	public function toYaml() : string {
		return yaml_emit([
			"name" => $this->name,
			"antigen" => $this->antigen,
			"version" => $this->version,
			"description" => $this->description,
			"authors" => $this->authors,
			"php" => $this->php,
			"api" => $this->api,
			"sharable" => $this->sharable,
		]);
	}
}
