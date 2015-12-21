<?php

/*
 * This File is part of the Lucid\Xml package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml;

use Lucid\Xml\Dom\DOMElement;
use Lucid\Xml\Dom\DOMDocument;
use Lucid\Xml\Loader\Loader;
use Lucid\Xml\Loader\LoaderInterface;
use Lucid\Xml\Traits\XmlHelperTrait;
use Lucid\Common\Helper\Str;
use Lucid\Common\Traits\Getter;

/**
 * @class Parser
 *
 * @package Lucid\Xml
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
class Parser implements ParserInterface
{
    use Getter,
        XmlHelperTrait;

    /** @var callable */
    private $pluralizer;

    /** @var callable */
    private $keyNormalizer;

    /**
     * @var array */
    private $options;

    /**
     * Creates a new `Parser` instance.
     *
     * @param LoaderInterface $loader
     */
    public function __construct(LoaderInterface $loader = null)
    {
        $this->loader = $loader ?: new Loader($this->getLoaderConfig());
        $this->options = [];
    }

    /**
     * Toggle on/off merging attributes to array keys.
     *
     * @param bool $merge
     *
     * @return void
     */
    public function setMergeAttributes($merge)
    {
        $this->options['merge_attributes'] = (bool)$merge;
    }

    /**
     * Set the attributes key name.
     *
     * The default key will be `@attributes`.
     * This will be ignored if merging attributes is active.
     *
     * @param string $key
     *
     * @return void
     */
    public function setAttributesKey($key)
    {
        $this->options['attribute_key'] = $key;
    }

    /**
     * Get the attributes key.
     *
     * Defaults to `@attributes`.
     *
     * @return string
     */
    public function getAttributesKey()
    {
        return $this->getDefault($this->options, 'attribute_key', '@attributes');
    }

    /**
     * Set the list identifier key.
     *
     * Elements that match with that key will always be considered a list,
     * as long as thy have any parent element.
     *
     * @param string $key
     *
     * @return void
     */
    public function setIndexKey($key)
    {
        $this->options['index_key'] = $key;
    }

    /**
     * Sets the index key used in attributes.
     *
     * @param string $key
     *
     * @return void
     */
    public function setIndexAttributeKey($key)
    {
        $this->options['index_attr_key'] = $key;
    }

    /**
     * getIndexKey
     *
     * @return mixed
     */
    public function getIndexKey()
    {
        return $this->getDefault($this->options, 'index_key', null);
    }

    /**
     * getIndexAttributeKey
     *
     * @return string
     */
    public function getIndexAttributeKey()
    {
        return $this->getDefault($this->options, 'index_attr_key', 'index');
    }

    /**
     * Set a custom function to normalize an xml node name to a php array key name.
     *
     * By default, hyphens are converted to underscores.
     *
     * @param callable $normalizer
     *
     * @return void
     */
    public function setKeyNormalizer(callable $normalizer)
    {
        $this->keyNormalizer = $normalizer;
    }

    /**
     * Set the pluralizer.
     *
     * @param callable $pluralizer
     *
     * @return void
     */
    public function setPluralizer(callable $pluralizer = null)
    {
        $this->pluralizer = $pluralizer;
    }

    /**
     * {@inheritdoc}
     */
    public function parseDom(\DOMDocument $xml)
    {
        if (!$xml instanceof DOMDocument) {
            $xml = $this->convertDocument($xml);
        }

        if (!$root = $xml->documentElement) {
            throw new \InvalidArgumentException('DOM has no root element');
        }

        return [$xml->documentElement->nodeName => $this->parseDomElement($root)];
    }

    /**
     * {@inheritdoc}
     */
    public function parse($xml)
    {
        $opts = $this->getLoaderConfig();
        $opts[LoaderInterface::FROM_STRING] = !(is_file($xml) && stream_is_local($xml));

        return $this->parseDom($this->loader->load($xml, $opts));
    }

    /**
     * {@inheritdoc}
     */
    public function parseDomElement(DOMElement $xml)
    {
        $result        = $this->parseElementNodes($xml->xpath('./child::*'), $xml->nodeName);
        $attributes    = $this->parseElementAttributes($xml);
        $text          = $this->prepareTextValue($xml, current($attributes) ?: null);
        $hasAttributes = (bool)$attributes;

        if ($hasAttributes) {

            if (null !== $text) {
                $result['value'] = $text;
            }

            if ($this->mergeAttributes()) {
                $attributes = $attributes[$this->getAttributesKey()];
            }

            $result = array_merge($attributes, $result);
            return $result;
        }

        if (null !== $text) {
            if (!empty($result)) {
                $result['value'] = $text;
            } else {
                $result = $text;
            }
            return $result;
        }

        return (!(bool)$result && null === $text) ? null : $result;
    }

    /**
     * Get the php equivalent of an input value derived from any king of xml.
     *
     * @param mixed $val
     * @param mixed $default
     * @param ParserInterface $parser
     *
     * @return mixed
     */
    public static function getPhpValue($val, $default = null, ParserInterface $parser = null)
    {
        if ($val instanceof DOMElement) {
            $parser = $parser ?: new static;

            return $parser->parseDomElement($val);
        }

        if (0 === strlen($val)) {
            return $default;
        }

        if (0 === strpos($val, '0x') && preg_match('#^[[:xdigit:]]+$#', substr($val, 2))) {
            return hexdec($val);
        }

        if (is_numeric($val)) {
            return ctype_digit($val) ? intval($val) : floatval($val);
        }

        if (in_array($lval = strtolower($val), ['true', 'false'])) {
            return $lval === 'true' ? true : false;
        }

        return $val;
    }

    /**
     * Get the text of a `DOMElement` excluding the contents
     * of its child elements.
     *
     * @param DOMElement $element
     * @param bool $concat
     *
     * @return string|array returns an array of strings if `$concat` is `false`
     */
    public static function getElementText(DOMElement $element, $concat = true)
    {
        $textNodes = [];

        foreach ($element->xpath('./text()') as $text) {

            if ($value = static::clearValue($text->nodeValue)) {
                $textNodes[] = $value;
            }
        }
        return $concat ? implode($textNodes) : $textNodes;
    }

    /**
     * clearValue
     *
     * @param mixed $text
     *
     * @return string|null
     */
    public static function clearValue($value)
    {
        return is_string($value) && 0 === strlen(trim($value)) ? null : $value;
    }

    /**
     * Convert hyphens to underscores.
     *
     * @param string $name
     *
     * @return string
     */
    public static function fixNodeName($name)
    {
        return strtr(Str::snakeCase($name), ['-' => '_']);
    }

    /**
     * Check if a given string is the list identifier.
     *
     * @param string $name
     * @param string $prefix
     *
     * @return bool
     */
    protected function isListKey($name, $prefix = null)
    {
        return $this->prefixKey($this->getIndexKey(), $prefix) === $name;
    }

    /**
     * Determine weather to merge attributes or not.
     *
     * @return bool
     */
    protected function mergeAttributes()
    {
        return $this->getDefault($this->options, 'merge_attributes', false);
    }


    /**
     * getLoaderConfig
     *
     * @return mixed
     */
    protected function getLoaderConfig()
    {
        return [
            LoaderInterface::FROM_STRING     => false,
            LoaderInterface::SIMPLEXML       => false,
            LoaderInterface::DOM_CLASS       => __NAMESPACE__.'\\Dom\DOMDocument',
            LoaderInterface::SIMPLEXML_CLASS => __NAMESPACE__.'\\SimpleXMLElement'
        ];
    }

    /**
     * Normalize a node key
     *
     * @param mixed $key
     *
     * @return mixed
     */
    protected function normalizeKey($key)
    {
        if (null !== $this->keyNormalizer) {
            return call_user_func($this->keyNormalizer, $key);
        }

        return static::fixNodeName($key);
    }

    /**
     * Convert bool like and numeric values to their php equivalent values.
     *
     * @param DOMElement $xml the element to get the value from
     * @param array $attributes
     * @return mixed
     */
    private function prepareTextValue(DOMElement $xml, array $attributes = null)
    {
        $text = static::getElementText($xml, true);

        return (isset($attributes['type']) && 'text' === $attributes['type']) ?
            static::clearValue($text) :
            static::getPhpValue($text, null, $this);
    }

    /**
     * Parse a nodelist into a array
     *
     * @param \DOMNodeList|array $children elements to parse
     * @param string $parentName           node name of the parent element
     *
     * @return array
     */
    private function parseElementNodes($children, $parentName = null)
    {
        $result = [];

        foreach ($children as $child) {
            $prefix = $child->prefix ?: null;
            $oname  = $this->normalizeKey($child->nodeName);
            $name   = $this->prefixKey($oname, $prefix);

            if (isset($result[$name])) {
                $this->parseSetResultNodes($child, $name, $result);
                continue;
            }

            $this->parseUnsetResultNodes($child, $name, $oname, $parentName, $result, $prefix);
        }

        return $result;
    }

    /**
     * Parse a `DOMElement` if a result key is set.
     *
     * @param DOMElement $child
     * @param string $name
     * @param array $result
     *
     * @return mixed|bool the result, else `false` if no result.
     */
    private function parseSetResultNodes(DOMElement $child, $name, array &$result = null)
    {
        if (!(is_array($result[$name]) && $this->valueIsList($result[$name]))) {
            return false;
        }

        $value = static::getPhpValue($child, null, $this);

        if (is_array($value) && $this->valueIsList($value)) {
            return $result[$name] = array_merge($result[$name], $value);
        }

        return $result[$name][] = $value;
    }

    /**
     * Parse a `DOMElement` if a result key is unset.
     *
     * @param DOMElement $child
     * @param string $name
     * @param string $oname
     * @param string $pName
     * @param array $result
     * @param string $prefix
     * @param array $attrs
     *
     * @return mixed the result
     */
    private function parseUnsetResultNodes(DOMElement $child, $name, $oname, $pName, array &$result, $prefix = null)
    {
        if ($this->isListKey($name, $prefix) || $this->isEqualOrPluralOf($this->normalizeKey($pName), $oname)) {
            // if attributes index is set, use it as index
            if (($index = $child->getAttribute($this->getIndexAttributeKey())) && 0 !== strlen($index)) {
                $child->removeAttribute($this->getIndexAttributeKey());

                return $result[static::getPhpValue($index)] = static::getPhpValue($child, null, $this);
            }

            return $result[] = static::getPhpValue($child, null, $this);
        }

        $value = static::getPhpValue($child, null, $this);

        if (1 < $this->getEqualNodes($child, $prefix)->length) {
            return $result[$name][] = $value;
        }

        return $result[$name] = $value;
    }

    /**
     * Parse element attributes into an array.
     *
     * @param DOMElement $xml
     *
     * @return array
     */
    private function parseElementAttributes(DOMElement $xml)
    {
        $attrs        = [];
        $elementAttrs = $xml->xpath('./@*');

        if (0 === $elementAttrs->length) {
            return $attrs;
        }

        foreach ($elementAttrs as $key => $attribute) {
            $value = static::getPhpValue($attribute->nodeValue, null, $this);
            $name = $this->normalizeKey($attribute->nodeName);
            $attrs[$this->prefixKey($name, $attribute->prefix ?: null)] = $value;
        }

        return [$this->getAttributesKey() => $attrs];
    }

    /**
     * Check if the input string is a plural or equal to a given comparative string.
     *
     * @param string $name the input string
     * @param string $singular the string to compare with
     *
     * @return bool
     */
    private function isEqualOrPluralOf($name, $singular)
    {
        return 0 === strnatcmp($name, $singular) || 0 === strnatcmp($name, $this->pluralize($singular));
    }

    /**
     * Attempt to pluralize a string.
     *
     * @param string $singular
     *
     * @return string
     */
    private function pluralize($singular)
    {
        if (null === $this->pluralizer) {
            return $singular;
        }

        return call_user_func($this->pluralizer, $singular);
    }

    /**
     * A lookahead to find sibling elements with similar names.
     *
     * @param DOMElement $node the node in charge.
     * @param string     $prefix the element prefix
     *
     * @return \DOMNodeList
     */
    private function getEqualNodes(DOMElement $node, $prefix = null)
    {
        $name = $this->prefixKey($node->nodeName, $prefix);

        return $node->xpath(
            sprintf(".|following-sibling::*[name() = '%s']|preceding-sibling::*[name() = '%s']", $name, $name)
        );
    }

    /**
     * Prepend a string.
     *
     * Will pass-through the original string if `$prefix` is `null`.
     *
     * @param string $key the key to prefix
     * @param string $prefix the prefix
     *
     * @return string
     */
    private function prefixKey($key, $prefix = null)
    {
        return $prefix ? sprintf('%s:%s', $prefix, $key) : $key;
    }

    /**
     * Converts a `\DOMDocument`that is not an instance of
     * `Selene\Module\Dom\DOMDocument`.
     *
     * @param \DOMDocument $xml the document to convert
     *
     * @return DOMDocument
     */
    private function convertDocument(\DOMDocument $xml)
    {
        return $this->loader->load($xml->saveXML(), [LoaderInterface::FROM_STRING => true]);
    }
}
