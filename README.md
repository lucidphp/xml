# XML writer and parser utilities

[![Author](http://img.shields.io/badge/author-iwyg-blue.svg?style=flat-square)](https://github.com/iwyg)
[![Source Code](http://img.shields.io/badge/source-lucid/signal-blue.svg?style=flat-square)](https://github.com/lucidphp/xml/tree/local-dev)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/lucidphp/xml/blob/local-dev/LICENSE.md)

[![Build Status](https://img.shields.io/travis/lucidphp/xml/local-dev.svg?style=flat-square)](https://travis-ci.org/lucidphp/xml)
<!--
[![Code Coverage](https://img.shields.io/coveralls/lucidphp/xml/local-dev.svg?style=flat-square)](https://coveralls.io/r/lucidphp/xml)
-->
[![HHVM](https://img.shields.io/hhvm/lucid/xml/local-dev.svg?style=flat-square)](http://hhvm.h4cc.de/package/lucid/xml)

## Installing

```bash
$ composer require lucid/xml
```

## Testing

Run tests with:

```bash
$ ./vendor/bin/phpunit
```

## The Parser

The `Parser` class can parse xml string, files, DOMDocuments, and DOMElements
into a php array.

### Parsing xml strings
```php
<?php

use Lucid\Xml\Parser;

$parser = new Parser;

$parser->parse('<data><foo>bar</foo></data>');

```

### Parsing xml files

```php
<?php

use Lucid\Xml\Parser;

$parser = new Parser;

$parser->parse('/path/to/data.xml');

```

### Parsing a `DOMDocument`

```php
<?php

use Lucid\Xml\Parser;

$parser = new Parser;

$parser->parseDom($dom);

```

### Parsing a `DOMElement`

```php
<?php

use Lucid\Xml\Parser;

$parser = new Parser;

$parser->parseDomElement($element);

```

## Parser Options

### Dealing with Attributes
Xml attributes are captured as an deticated section within the actual node data.
The section key defaults to `@attributes`, but can be changed using the `setAttributesKey` method.

```php
<?php

use Lucid\Xml\Parser;

$xml = '<data><node id="1">some text</node></data>'

$parser = new Parser;
$parser->setAttributesKey('__attrs__');
$parser->parse($xml);
```

```php
['data' => ['node' => ['__attrs__' => ['id' => 1], 'value' => 'some text']]];
```
#### Merging attributes
Setting `Parser::mergeAttributes(true)` will merge any attributes as key/value
into the data set.

```php
$parser->setMergeAttributes(true);
$parser->parse($xml);
```
The above example will output something simmilar to this:

```php
['data' => ['node' => ['id' => 1, 'value' => 'some text']]];
```

### Normalizing keys

You may specifay how keys are being transformed by setting a key normalizer callback.

The default normalizer transforms dashes to underscores and camelcase to snakecase notation.

```php
<?php

use Lucid\Xml\Parser;

$parser = new Parser;

$parser->setKeyNormalizer(function ($key) {
	// do string transfomations
	return $key;
});

$parser->parseDomElement($element);

```

### Set index key

This forces the parser to treat nodes with a nodeName of the given key to be
handled as list. 


```php
<?php

use Lucid\Xml\Parser;

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

use Lucid\Xml\Writer;

$writer = new Writer;

$data = [
	'foo' => 'bar'
];

$writer->dump($data); // <root><foo>bar</foo></root>

// set the xml root node name:

$writer->dump($data, 'data'); // <data><foo>bar</foo></data>

```

### Dumping php data to a DOMDocument

Note: this will create an instance of `Lucid\Xml\Dom\DOMDocument`.

```php

<?php

use Lucid\Xml\Writer;

$writer = new Writer;

$data = [
	'foo' => 'bar'
];

$dom = $writer->writeToDom($data);

```

## Writer options

### Set the normalizer instance

Normaly, the `NormalizerInterface` implementation is set for you when instantiating a new `Writer`, however you can set your own normalizer instance.

Note: the normalizer must implement the `Lucid\Xml\Normalizer\NormalizerInterface` interface.

```php
<?php

use Lucid\Xml\Writer;
use Lucid\Xml\Normalizer\Normalizer;

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

### Set value keys

```php
<?php

$data = [
	'foo' => [
		'@attributes' => [
			'bar' => 'baz'
		],
		'value' => 'tab'
	]
];
```

The data structure above would dump the following xml string

```xml
<foo bar="baz"><value>tab</value></foo>
```

However, if you need the value node as actual value of the parent node, you may
use `Writer::useKeyAsValue(string $key)` to do so

```php
<?php

$writer->useKeyAsValue('value');

$writer->dump($data);
```

now dumps:
```xml
<foo bar="baz">tab</foo>
```

### Writing indexed array structure

Indexed arrays will create xml structures like the example below:

```php
$data = ['data' => [1, 2, 3]];
$writer->dump($data);
```

```xml
<data>
    <item>1</item>
    <item>2</item>
    <item>3</item>
</data>
```

You can change the node names associated with indexed items by using the
`useKeyAsIndex(string $key)` method.

```php
$writer->useKeyAsIndex('thing');
$writer->dump($data);
```

```xml
<data>
	<thing>1</thing>
	<thing>2</thing>
	<thing>3</thing>
</data>
```

### Writing arrays with none valid index keys

Arrays containing invalid indices (e.g. unordererd lists) will be treated
slightly different.

```php
$data = ['data' => [1 => 'foo', 4 => 'bar', 3 => 'baz']];
$writer->dump($data);
```

```xml
<data>
    <item index="1">foo</item>
    <item index="4">bar</item>
    <item index="3">baz</item>
</data>
```
