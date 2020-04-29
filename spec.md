# Virion Framework Specification, Version 2.0 [DRAFT]
Drafted by SOFe in April 2020.

## Abstract
"Virions" are PHP libraries designed to support namespace shading.
Special interest is given on integrating with usage in PocketMine plugin ecosystem.

This specification specifies the terminologies for the virion framework,
as well as the requirements for a library to serve as a virion
and for a project to use a virion.

See definition of terms at the bottom of this document.

## Virion source
The source of a virion must satisfy the following properties.

### virion.yml
The root directory of the virion project must contain a `virion.yml` file in YAML format,
containing the following attributes:

| Attribute | Required | Description | Type | Example |
| :---: | :---: | :---: | :---: | :---: |
| `name` | true | The name of the virion, only used for human-readable display in developer tools | string | `libasynql` |
| `description` | false | A description of the virion, only used for human-readable display in developer tools | string | `Simple asynchronous SQL access` |
| `authors` | false | The authors of the virion, only used for human-readable display in developer tools | list of strings | `[SOFe]` |
| `antigen` | true | The antigen namespace | string | `poggit\libasynql` |
| `version` | true | The version of the virion | string | `3.0.0`
| `php` | Either `php` or `api` | The PHP versions required, assuming PHP is semver-compliant | string list | `["5.6", "7.0"]` is compatible with PHP 5.6.\* and 7.\*.\* |
| `api` | Either `php` or `api` | The PocketMine API version constraints, equivalent to `api` in plugin.yml for PocketMine plugins | string list | `["3.0.0-ALPHA13, 3.0.0"]` |
| `sharable` | false | The sharable namespace, if any | string | `sharable\poggit\libasynql` |

### PHP Code
#### Non-sharable PHP code
The non-sharable PHP code of a virion are placed under the `src` subdirectory in the root directory.
Within the `src` subdirectory, all code must follow either PSR-0 or PSR-4 structure.
All non-sharable namespace items must be under the antigen,
so the existence of the nonempty subdirectory `src/${antigen}` is used as a predicate
to determine whether PSR-0 or PSR-4 is adopted.

Note that virion code must follow either PSR-0 or PSR-4 strictly **under all circumstances**
other than those written directly in the entry file.
That is, even if a class is only used inside a certain method of some other class,
they must still not share the same file (even though autoloading is not required).
<!--
Nothing necessarily requires this yet,
but for future compatibility reasons let's enforce this.
For example, if the injector or runtime needs to check the file that declares a class for sanity check,
there might be some problems if one file declares multiple classes.
-->

#### Sharable PHP code
The sharable PHP code of a virion are placed under the `share` subdirectory in the root directory,
with PSR-0 or PSR-4 structure determined by a similar predicate.

#### Entry PHP code
If the file `src/${antigen}/entry.php` (PSR-0) or `src/entry.php` (PSR-4) exists,
the code inside would be loaded during consumer entry.
Code in entry.php may assume its relative path to other files
in `src/${antigen}/entry.php` (PSR-0) or `src/entry.php` (PSR-4),
but not any other files.

If further entry files are manually included from `entry.php`,
they must also be placed inside `src/${antigen}` (PSR-0) or `src` (PSR-4).
Conventionally, prefer file names in `snake_case` to distinguish from class files.

#### Guaranteed and undefined assumptions
Code in `src/${antigen}` (PSR-0) or `src` (PSR-4) may assume their relative path
to other files also in the directory.
Other than this, no assumption about the path of PHP files may be made.
This includes:
- whether `__FILE__` starts with `phar://`.
	Virions may be loaded in source or injected form.
- whether `__FILE__` contains `src/` somewhere.
	Although this holds for most tools and most scenarios,
	this is not guaranteed for future compatibility.
- what class loader is used to load virion classes,
	not even whether classes are loaded with the same class loader.
	In threaded scenarios, some obvious assumptions may not hold.

Entry files must expect that entry files are modified drastically,
such as inserting new statements at the end of the file.
Furthermore, entry files must not include a `__halt_compiler();` statement,
and must not have a terminating `?>` tag anywhere.

#### Namespace referencing
Non-sharable PHP code must only reference its own namespace using syntactic references,
as it is subject to refactor during shading.

#### Ungated global resources
Non-sharable PHP code must be aware that multiple instances of the same code,
even if gated by a static property, might be loaded in the same PHP runtime.
For example, if the virion registers stream wrapper
through [`stream_wrapper_register`](https://php.net/stream-wrapper-register),
the protocol name must either be supplied by the consumer,
or be evaluated based on the namespace (potentially the antibody).
A similar exmaple applies to naming global variables.

### Asset files
Non-code files can be placed in the same directory as some non-sharable PHP file,
and can be loaded in runtime from the PHP file with the path `__DIR__ . "/asset.file"`.
It is *only* guaranteed that a PHP file `src/path/to/Class.php`
preserves its relative path to an asset file
if the asset file is transitively under its parent directory, i.e. `src/path/to`.
It is *undefined* whether and how shading tools would copy files
under `src` but not under the `src/${antigen}` directory in PSR-0.
Furthermore, it is *undefined* whether and how shading tools would copy
non-PHP files or non-PSR-[0/4]-compliant under `sharable`.
However, shading tools would ignore all files under the project directory other than `virion.yml`, `src` and `sharable`.

Unlike plugin resources, virions do *not* have a specific directory for assets.
All asset files are under `src` no matter in source form or compiled form.

## Virion distributable
Virions are distributed as phar files with the following format:

### virion.yml
The following fields always exist,
using either values from the source virion.yml or the default values:
- `name`
- `description` (empty string if not declared)
- `authors` (empty array if not declared)
- `antigen`
- `version`
- `php` (`null` if unrestricted)
- `api` (`null` if unrestricted)
- `sharable` (`null` if not declared)

### `src`
All non-sharable files are placed in `src` under PSR-0 structure,
regardless whether the source form uses PSR-4 structure.

An `src/${antigen}/entry.php` is created regardless the source includes it or not.
After all original statements are executed,
the entry script executes equivalent code to the following in global context:
```php
$_VIRION_ANTIGENS = $_VIRION_ANTIGENS ?? [];
$_VIRION_ANTIGENS[\antigen::class] = [
	"name" => "virion name",
	"version" => "virion version",
	"shaded-psr-items" => [
		// for each non-sharable PSR-item named antigen\Foo\Bar
		"antigen\\Foo\\Bar" => \antigen\Foo\Bar::class,
	],
];

$_VIRION_SHARABLE_MD5 = $_VIRION_SHARABLE_MD5 ?? [];
// for each sharable PSR-item named shared\name\space\Foo\Bar
// with "HASH" being the hex representation of its functional-md5 hash
if(isset($_VIRION_SHARABLE_MD5[\share\name\space\Foo\Bar::class])) {
	$other = $_VIRION_SHARABLE_MD5[\share\name\space\Foo\Bar::class];
	if($other["H"] !== "HASH") {
		throw new \CompileError(sprintf("Virion classloading conflict: " .
				"Functionally inequivalent variants of %s are loaded by %s v%s and %s v%s respectively " .
				"with functional hashes %s and %s"),
			\share\name\space\Foo\Bar::class, "virion name", "virion version", $other["name"], $other["version"], "HASH", $other["H"]);
	}
} else {
	$_VIRION_SHARABLE_MD5[\share\name\space\Foo\Bar::class] = [
		"name" => "virion name",
		"version" => "virion version",
		"H" => "HASH",
	];
}
```

Note that after shading operation,
`\antigen::class` would be changed into `\antibody::class`,
which resolves into the antibody namespace,
while `"antigen"` would remain the antigen namespace.

All non-PSR files, including PHP files and asset files, are copied with relative paths to those in `src` preserved.

## Consumer
A consumer must contain in root directory a `virion.yml` file in YAML format,
containing a `libs` attribute, which is a list of mappings.
Each mapping contains the following attributes:

| Name | Description | Required / Default value |
| :---: | :---: | :---: |
| `src` | The vendor-dependent  virion identifier | Required |
| `version` | A semver constraint for the virion version | Required |
| `vendor` | The vendor URL for the virion | Default `https://poggit.pmmp.io/v.dl` |
| Other attributes | String values passed as GET parameters when downloading the distributable | N/A |

Virion distributables are expected to be downloadable from `${vendor}/${src}/${version}`,
along with the GET parameters from other attributes.

## Shading tools
A shading tool should perform the following operations to shade and inject a virion into a consumer:

## Definition of terms
### PHP
#### Namespace item
A "namespace item" is an element of the PHP language
that is uniquely identified by its name under a namespace.
As of PHP 7.0, this includes the following:
- A class
- A interface
- A trait
- A function
- A constant

Note that global variables are not considered namespace items
because they are namespace-insensitive.

#### PSR item
A "PSR item" is a namespace item with its own file
as specified by the PSR-0 or PSR-4 standard.

The following quote is taken from the PSR-4 standard:
> The term “class” refers to classes, interfaces, traits, and other similar structures.

A PSR item refers to the term "class" as specified by PSR-4.

#### Transitively under
A namespace item is considered "transitively under" a namespace `a\b\c`
if its fully-qualified name starts with `a\b\c\`.

#### Functionally equivalent
Two namespace items `A`, `B` are "functionally equivalent" if
for all deterministic function `h: mixed -> T` and deterministic binary operator `^ : (T, T) -> T`,
the following algorithm `H` results in `H(A) === H(B)` and `H(A) !== null`:
```
algorithm H($item) {
	if($item is a trait or a function) {
		return null;
	}

	$output = h(fully-qualified name of $item);

	if($item is a class) {
		$output ^= h("class");
		if($item is abstract) $output ^= h("abstract");
		if($item is final) $output ^= h("final");
	}

	if($item is an interface) {
		$output ^= h("interface");
	}

	if($item is a class or an interface) {
		$array = [];
		foreach(constants in $item as $constant) {
			$array[name of $constant] = clean_tokens($constant);
		}
		foreach(class properties in $item as $property) {
			$array[name of $property] = clean_tokens($property);
		}
		foreach(methods in $item as $method) {
			$array[name of $method] = clean_tokens($method);
		}
		ksort($array);
		$output ^= h($array);
	}

	if($item is a constant) {
		$output ^= clean_tokens($item);
	}

	return $output;
}

function clean_tokens($declaration) {
	$tokens = token_get_all($declaration);
	$output = h(null);
	foreach($tokens as $token) {
		if(is_array($token)) {
			if(!in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
				$output ^= h($token[0]);
				$output ^= h(trim($token[1]));
			}
		} else {
			$output ^= h($token);
		}
	}
	return $output;
}
```

The result of `H(A)` on namespace item `A` is called the "functional-`h` hash" of `A`.

A more intuitive equivalent definition is that two namespace items are "functionally equivalent"
if and only if they satisfy the following properties:
- They have the same fully-qualified name and of the same item type
	(e.g. both are classes, both are interfaces, etc.).
- Two functions are *never* functionally equivalent.
- If they are both constants, they must be defined using the `const XXX = value;` syntax
	(instead of the `define()` syntax),
	and the value declaration must be "token-identical"
	(i.e. they must have the identical order of non-whitespace non-(doc)comment tokens).
	For example, `const THREE = 1+2;`, `const THREE = 0x1+0x2;` and `const THREE = 2+1;`
	are pairwise *not* token-identical,
	while `const THREE = 1+2;` and `/** three */ const THREE=1 + 2;` are token-identical.
	Furthermore, token-identical is alias-sensitive,
	i.e. `const EOL = PHP_EOL;` and `const EOL = \PHP_EOL;` are *not* token-identical.
- If they are both classes or both interfaces,
	their contents must be token-identical,
	except the order of items can be swapped
	(but must have the same unordered set of items).
	In particular, the names of interface method arguments must be identical,
	and the aliases used for types must be identical.
- Two traits are *never* functionally equivalent.

#### Syntactic reference
A "syntactic reference" to a namespace item is
either an absolutely named reference
or an unnamed references such as `self::class`, `parent::class`, etc.

Absolutely named references are identified
if their token stream, with whitespaces and comments striped, follows this BNF pattern:
```bnf
(T_NAMESPACE | (T_USE (T_FUNCTION | T_CONST)?) | T_NS_SEPARATOR) (T_STRING T_NS_SEPARATOR)* T_STRING
```

The following examples are syntactic references
to the namespace item `poggit\libasynql\SqlResult`:
```php
use poggit\libasynql\SqlResult;

$result = new \poggit\libasynql\SqlResult;

use poggit\libasynql\SqlResult as Result;
$class = new \ReflectionClass(Result::class);

$class = new \ReflectionClass(\poggit\libasynql\SqlResult::class);
```

The following examples are syntactic references
to the namespace item `poggit\libasynql\SqlResult`
from the scope of a method in the class `poggit\libasynql\SqlResult`:
```php
$class = new \ReflectionClass(self::class);
$class = new \ReflectionClass(static::class);
$class = new \ReflectionClass(get_class());
$class = new \ReflectionClass(__CLASS__);
$class = new \ReflectionClass(__NAMESPACE__ . "\\SqlResult");
```

The following examples are *not* syntactic references:
```php
$class = new \ReflectionClass("poggit\\libasynql\\SqlResult");
$class = new \ReflectionClass('poggit\libasynql\SqlResult');
```

The following example, even though in the scope of a method in the class `poggit\Meta`,
is *not* a syntactic reference if the antigen is `poggit\libasynql`:
```php
$class = new \ReflectionClass(libasynql\SqlResult::class);
```

### Virion
#### Virion
A "virion" is a library designed to support namespace shading.
In this specification, any object is named starting with `lib` if and only if it is a virion.

#### Consumer
A "consumer" of a virion `libx` is a library or project
(in particular, other virions or PocketMine plugins)
that uses the virion `libx`.

#### Version
The "version" of a virion is a [semver](https://semver.org) version string.
Released versions of a virion must follow semver requirements.

#### Antigen
The "antigen" of a virion is a namespace uniquely claimed by the virion,
such that it is assumed all namespace items transitively under this namespace
are declared only inside the virion.
All non-sharable (defined below) namespace items of a virion are placed transitively under the antigen.
Conventionally, the antigen is in the form `Author\VirionName`.

#### Antibody
The "antibody" of a virion in a consumer
is the namespace that the antigen is refactored into during shading.
Conventionally, if the consumer is a virion, the antibody is `${consumer.antigen}\libs\${virion.antigen}`.
If the consumer is a plugin with main class `a\b\c\Main`, the antibody is `a\b\c\libs\${virion.antigen}`.

#### Sharable
A PSR item with fully-qualified name `a\b\c\D` of a virion is "sharable"
if across all compatible or incompatible versions of this virion
where a namespace item with the fully-qualified name `a\b\c\D` exists,
the corresponding items in such versions must be pairwise functionally equivalent,
and must not reference or otherwise depend on any non-shareable namespace items of the same virion.
Sharable namespace items are not to be shaded,
and can be used as mutually compatible type hints.

In version 2.0, sharable is only defined for PSR items.
This definition may be extended to other namespace items in future compatible versions of this specification.

#### Sharable namespace
The "sharable namespace" of a virion
is a namespace uniquely claimed by the virion,
such that it is assumed all namespace items transitively under this namespace
are declared only inside the virion.
All sharable namespace items of a virion are placed transitively under the sharable namespace.
The antigen and the sharable namespace are mutually exclusive.
Conventionally, the sharable namespace is in the form `Sharable\Author\VirionName`.

#### Virion Entry file
A virion may have an "entry file",
which is guaranteed to be `require_once`'ed at the end of the entry of the consumer.

#### Consumer entry
The "entry" of a consumer plugin is the autoloading of its main class file.
The "entry" of a consumer virion is the loading of its entry file (one is created if not exists).
