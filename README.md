# Selene Component for loading, parsing and, writing xml


## Testing

Run tests with: 

```bash
$ vendor/bin/phpunit
```

## The Parser

The `Parser` class can parse xml string, files, DOMDocuments, and DOMElements
to a php array. 


### Parsing xml strings
```php
<?php

use \Selene\Module\Xml\Parser;

$parser = new Parser;

$parser->parse('<data><foo>bar</foo></data>');

```

### Parsing xml files

```php
<?php

use \Selene\Module\Xml\Parser;

$parser = new Parser;

$parser->parse('/path/to/data.xml');

```

### Parsing a `DOMDocument`

```php
<?php

use \Selene\Module\Xml\Parser;

$parser = new Parser;

$parser->parseDom($dom);

```

### Parsing a `DOMElement`

```php
<?php

use \Selene\Module\Xml\Parser;

$parser = new Parser;

$parser->parseDomElement($element);

```

## Parser Options

### Merge attributes


```php
<?php

use \Selene\Module\Xml\Parser;

$parser = new Parser;

$parser->setMergeAttributes(true);

```

### Normalizing keys

You my specifay how keys are transformed by setting a key normalizer callback.

The default normalizer transforms dashes to underscores and camelcase to snakecase notation.

```php
<?php

use \Selene\Module\Xml\Parser;

$parser = new Parser;

$parser->setKeyNormalizer(function ($key) {
	// do string transfomations
	return $key;
});

$parser->parseDomElement($element);

```

### Set the attributes key

If attribute merging is disabled, use this to change the default attributes key
(default is `@attributes`).


```php
<?php

use \Selene\Module\Xml\Parser;

$parser = new Parser;

$parser->setAttributesKey('@attrs');

```

### Set index key

This forces the parser to treat nodes with a nodeName of the given key to be
handled as list. 


```php
<?php

use \Selene\Module\Xml\Parser;

$parser = new Parser;

$parser->setIndexKey('item');

```

### Set a pluralizer

By default the parser will parse xml structures like


```xml
<entries>
	<entry>1</entry>
	<entry>2</entry>
</entries>

```

To something like:

```php
<?php

['entries' => ['entry' => [1, 2]]]

```

Setting a pluralizer can fix this. 

Note, that a pluralizer can be any [callable](http://www.php.net/manual/en/language.types.callable.php) that takes a string and returns
a string.


```php
<?php

$parser->setPluralizer(function ($string) {
	if ('entry' === $string) {
		return 'entries';
	}
});

```

```php
<?php
['entries' => [1, 2]]
```

## The Writer

### Dumping php data to a xml string

```php
<?php

use \Selene\Module\Xml\Writer;

$writer = new Writer;

$data = [
	'foo' => 'bar'
];

$writer->dump($data); // <root><foo>bar</foo></root>

// set the xml root node name:

$writer->dump($data, 'data'); // <data><foo>bar</foo></data>

```

### Dumping php data to a DOMDocument

Note: this will create an instance of `Selene\Module\Xml\Dom\DOMDocument`.

```php

<?php

use \Selene\Module\Xml\Writer;

$writer = new Writer;

$data = [
	'foo' => 'bar'
];

$dom = $writer->writeToDom($data);

```

##Writer options

### Set the normalizer instance

Normaly, the `NormalierInterface` implementation is set for you when instantiating a new `Writer`, however you can set your own normalizer instance.

Note: the normalizer must implement the `Selene\Module\Xml\Normalizer\NormalierInterface` interface.

```php
<?php

use \Selene\Module\Xml\Writer;
use \Selene\Module\Xml\Normalizer\Normalizer;

$writer = new Writer(new Normalizer);

// or

$writer->setNormalizer($myNormalizer);
```

### Set the inflector

The inflector is the exact oppoite of the Parser's pluralizer. It singularizes
strings.


```php
<?php

$writer->setInflector(function ($string) {
	if ('items' === $string) {
		return 'item';
	}
});

```

### Set the document encoding

Default encoding is `UTF-8`.

```php
<?php
$writer->setEncoding($encoding); // string
```

### Set an attribute key map

This is usefull if you want to output certain keys as xml attribute 

```php
<?php

$writer->setKeyMap([
	'nodeName' => ['id', 'entry'] // nested keys 'id' and 'entry' of the key
	element 'nodeName' will be set as attributes instead of childnodes.
]);

```
Note: you can also use use `addMappedAttribute($nodeName, $attributeName)` to add more mapped attributes.
