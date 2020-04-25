# Virion Framework Specification, Version 2.0 [DRAFT]
Drafted by SOFe in April 2020.

## Abstract
"Virions" are PHP libraries designed to support namespace shading.
Special interest is given on integrating with usage in PocketMine plugin ecosystem.

This specification specifies the terminologies for the virion framework,
as well as the requirements for a library to serve as a virion
and for a project to use a virion.

## Definition of terms
### PHP
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

A namespace item is considered "transitively under" a namespace `a\b\c`
if its fully-qualified name starts with `a\b\c\`.

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

"Syntactic reference": TODO

### Virion
A "virion" is a library designed to support namespace shading.
In this specification, any object is named starting with `lib` if and only if it is a virion.

A "consumer" of a virion `libx` is a library or project
(in particular, other virions or PocketMine plugins)
that uses the virion `libx`.

The "antigen" of a virion is the namespace uniquely claimed by the virion,
such that it is assumed all namespace items transitively under this namespace
are declared only inside the virion.

The "antibody" of a virion in a consumer
is the namespace that the antigen is refactored into during shading.

The "epitope" of a shading operation of a virion in a consumer
is the namespace that is going to be appended to by the antigen of the virion
such that the resulting antibody is in the form `${epitope}\${antigen}.

If a namespace item with fully-qualified name `a\b\c\D` of a virion is "sharable",
across all compatible or incompatible versions of this virion
where a namespace item with the fully-qualified name `a\b\c\D` exists,
the corresponding items in such versions must be pairwise functionally identical,
and must not reference or otherwise depend on any non-shareable namespace items of the same virion

