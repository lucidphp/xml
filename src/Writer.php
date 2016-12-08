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

use Lucid\Common\Helper\Arr;
use Lucid\Xml\Dom\DOMElement;
use Lucid\Xml\Dom\DOMDocument;
use Lucid\Xml\Traits\XmlHelperTrait;
use Lucid\Xml\Normalizer\Normalizer;
use Lucid\Xml\Normalizer\NormalizerInterface;

/**
 * @class Writer
 *
 * @package Lucid\Xml
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
class Writer
{
    use XmlHelperTrait;

    /** @var DOMDocument */
    private $dom;

    /** @var string */
    private $encoding;

    /** @var NormalizerInterface */
    private $normalizer;

    /** @var callable */
    private $inflector;

    /** @var mixed */
    private $attributemap;

    /** @var string */
    private $nodeValueKey;

    /** @var string */
    private $indexKey;

    /** @var string */
    private $indexAttrKey;

    /**
     * Create a new xml writer instance.
     *
     * @param NormalizerInterface $normalizer the normalizer instance.
     * @param string $encoding the default encoding
     *
     */
    public function __construct(NormalizerInterface $normalizer = null, $encoding = 'UTF-8')
    {
        $this->setNormalizer($normalizer ?: new Normalizer);
        $this->setEncoding($encoding);

        $this->attributemap = [];
        $this->indexKey = 'item';
    }

    /**
     * Sets the encoding.
     *
     * @param mixed $encoding
     *
     * @return void
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     * Gets the encoding.
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Sets the Normalizer.
     *
     * @param NormalizerInterface $normalizer
     *
     * @return void
     */
    public function setNormalizer(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Returns the normalizer.
     *
     * @return Thapp\XmlBuilder\NormalizerInterface
     */
    public function getNormalizer()
    {
        return $this->normalizer;
    }

    /**
     * Sets the inflector.
     *
     * @param callable $inflector
     *
     * @return void
     */
    public function setInflector(callable $inflector)
    {
        $this->inflector = $inflector;
    }

    /**
     * Sets the Nodename => attribtues map.
     *
     * @param array $map
     *
     * @return void
     */
    public function setAttributeMap(array $map)
    {
        $this->attributemap = $map;
    }

    /**
     * Adds a mapped attribute to a nodename.
     *
     * @param string $nodeName
     * @param string $attribute
     *
     * @return void
     */
    public function addMappedAttribute($nodeName, $attribute)
    {
        $this->attributemap[$nodeName][] = $attribute;
    }

    /**
     * Tells if a node name as a mapped attribute of `$key`.
     *
     * @param string $name
     * @param string $key
     *
     * @return bool
     */
    public function isMappedAttribute($name, $key)
    {
        $map = isset($this->attributemap[$name]) ? $this->attributemap[$name] : [];

        if (isset($this->attributemap['*'])) {
            $map = array_merge($this->attributemap['*'], $map);
        }

        return in_array($key, $map);
    }

    /**
     * Sets the key that indicates a node value.
     *
     * @param string $key
     * @param bool $normalize
     *
     * @throws \InvalidArgumentException if $key is not null and an invalid nodename.
     * @return void
     */
    public function useKeyAsValue($key, $normalize = false)
    {
        if (true !== $normalize && !$this->isValidNodeName($key)) {
            throw new \InvalidArgumentException(sprintf('%s is an invalid node name', $key));
        }

        $this->nodeValueKey = $normalize ? $this->normalizer->normalize($key) : $key;
    }

    /**
     * Sets the index key used as key -> node identifier.
     *
     * @param string $key
     * @param bool $normalize
     *
     * @throws \InvalidArgumentException if $key is an invalid nodename.
     * @return void
     */
    public function useKeyAsIndex($key, $normalize = false)
    {
        if (null === $key) {
            $this->indexKey = null;
            return;
        }

        if (true !== $normalize && !$this->isValidNodeName($key)) {
            throw new \InvalidArgumentException(sprintf('%s is an invalid node name', $key));
        }

        $this->indexKey = $normalize ? $this->normalizer->normalize($key) : $key;
    }

    /**
     * Sets the index key used in attributes.
     *
     * @param string $key
     * @param bool $normalize
     *
     * @return void
     */
    public function useKeyAsIndexAttribute($key, $normalize = false)
    {
        if (null === $key) {
            $this->indexAttrKey = null;
            return;
        }

        if (true !== $normalize && !$this->isValidNodeName($key)) {
            throw new \InvalidArgumentException(sprintf('%s is an invalid attribute name', $key));
        }

        $this->indexAttrKey = $normalize ? $this->normalizer->normalize($key) : $key;
    }

    /**
     * Dump the input data to a xml string.
     *
     * @param mixed $data
     * @param string $rootName the xml root element name
     *
     * @return string
     */
    public function dump($data, $rootName = 'root')
    {
        $dom = $this->writeToDom($data, $rootName);

        return $dom->saveXML();
    }

    /**
     * Write the input data to a DOMDocument
     *
     * @param mixed $data
     * @param string $rootName the xml root element name
     *
     * @return DOMDocument
     */
    public function writeToDom($data, $rootName = 'root')
    {
        $this->buildXML(
            $dom = new DOMDocument('1.0', $this->getEncoding()),
            $root = $dom->createElement($rootName),
            $data
        );
        $dom->appendChild($root);

        return $dom;
    }

    /**
     * buildXML
     *
     * @param DOMNode $node
     * @param mixed $data
     *
     * @return void
     */
    protected function buildXML(DOMDocument $dom, \DOMNode $node, $data)
    {
        $normalizer = $this->getNormalizer();

        if (null === $data) {
            return;
        }

        if ((is_array($data) || $data instanceof \Traversable) && !$this->isXmlElement($data)) {
            return $this->buildXmlFromTraversable($dom, $node, $normalizer->ensureBuildable($data));
        }

        $this->setElementValue($dom, $node, $data);
    }

    /**
     * buildXmlFromTraversable
     *
     * @param \DOMDocument $dom
     * @param \DOMNode $node
     * @param mixed $data
     *
     * @return void
     */
    protected function buildXmlFromTraversable(\DOMDocument $dom, \DOMNode $node, $data)
    {
        $normalizer    = $this->getNormalizer();
        $hasAttributes = false;
        $isList        = is_array($data) && Arr::isList($data, true);

        foreach ($data as $key => $value) {
            if (!is_scalar($value) && !($value = $normalizer->ensureBuildable($value))) {
                continue;
            }

            if ($this->mapAttributes($node, $normalizer->normalize($key), $value)) {
                $hasAttributes = true;
                continue;
            }

            // ensure "node value keys" are at the bottom.
            if (null !== $this->nodeValueKey && is_array($value) && isset($value[$this->nodeValueKey])) {
                $val = $value[$this->nodeValueKey];
                unset($value[$this->nodeValueKey]);
                $value[$this->nodeValueKey] = $val;
            }

            // Set the default index key if there's no other way.
            if (is_int($key) || !$this->isValidNodeName($key)) {
                $cey = $key;
                $key = $this->indexKey;
                // In order to keep the original index for none "list-like" arrays
                // (numeric keys and none zero based indecies) add the original key
                // as index attribute
                if (is_int($cey) && !$isList) {
                    $value = [
                        '@AAA' => [$this->indexAttrKey ?: 'index' => $cey],
                        $this->nodeValueKey ?: 'value' => $value,
                    ];
                }
            }

            if (is_array($value) && !is_int($key) && $this->appendDomList($normalizer, $dom, $node, $key, $value)) {
                continue;
            }

            // if this is a non scalar value at this time, just set the
            // value on the element
            if ($this->isXMLElement($value)) {
                $element = $dom->createElement($normalizer->normalize($key));
                $node->appendChild($element);
                $this->setElementValue($dom, $element, $value);
                continue;
            }

            if ($this->isValidNodeName($key)) {
                $this->appendDOMNode($dom, $node, $normalizer->normalize($key), $value, $hasAttributes);
            }
        }
    }

    /**
     * appendDomList
     *
     * @param NormalizerInterface $normalizer
     * @param \DOMDocument $dom
     * @param mixed $node
     * @param mixed $key
     * @param mixed $value
     *
     * @return bool
     */
    protected function appendDomList(NormalizerInterface $normalizer, \DOMDocument $dom, $node, $key, $value)
    {
        if (!Arr::isList($value, true)) {
            return false;
        }

        if (($useKey = ($skey = $this->inflect($key)) && ($key !== $skey)) || (null !== $this->indexKey)) {
            $parentNode = $dom->createElement($key);

            if (!$useKey) {
                $parentNode = $dom->createElement($key);
                $this->buildXmlFromTraversable($dom, $parentNode, $value);
            } else {
                foreach ($value as $arrayValue) {
                    $this->appendDOMNode($dom, $parentNode, $skey, $arrayValue);
                }
            }

            return $node->appendChild($parentNode);
        }

        // if anything fails, append the domnodes directly.
        foreach ($value as $arrayValue) {
            $this->appendDOMNode($dom, $node, $this->inflect($normalizer->normalize($key)), $arrayValue);
        }

        return true;
    }

    /**
     * isValidNodeName
     *
     * @param mixed $name
     *
     * @return bool
     */
    protected function isValidNodeName($name)
    {
        return strlen($name) > 0 && false === strpos($name, ' ') && preg_match('#^[\pL_][\pL0-9._-]*$#ui', $name);
    }

    /**
     * inflect
     *
     * @param mixed $string
     *
     * @return string
     */
    protected function inflect($string)
    {
        if (null === $this->inflector) {
            return $string;
        }

        return call_user_func($this->inflector, $string);
    }

    /**
     * mapAttributes
     *
     * @return bool
     */
    private function mapAttributes(\DOMNode $node, $key, $value)
    {
        if (null !== $attrName = $this->getAttributeName($key)) {
            foreach ((array)$value as $attrKey => $attrValue) {
                $node->setAttribute($attrKey, $this->getAttributeValue($attrValue, $attrKey));
            }


            return true;
        }

        if (is_scalar($value) && $this->isMappedAttribute($node->nodeName, $key) && $this->isValidNodeName($key)) {
            $node->setAttribute($key, $this->getAttributeValue($value, $key));

            return true;
        }

        return false;
    }

    /**
     * setElementValue
     *
     * @param DOMDocument $dom
     * @param DOMElement $node
     * @param mixed $value
     *
     * @return null|bool returns false if no value was set, else null;
     */
    private function setElementValue(DOMDocument $dom, DOMElement $node, $value = null)
    {
        if ($value instanceof \SimpleXMLElement) {
            return $node->appendChild($dom->importNode(dom_import_simplexml($value), true));
        }

        if ($value instanceof \DOMDocument) {
            return $node->appendDomElement($value->firstChild);
        }

        if ($value instanceof \DOMElement) {
            return $dom->appendDomElement($value, $node);
        }

        if (is_array($value) || $value instanceof \Traversable) {
            return $this->buildXML($dom, $node, $value);
        }

        if (is_int($value) || is_float($value)) {
            return $this->createText($dom, $node, (string)$value);
        }

        if (is_bool($value)) {
            return $this->createText($dom, $node, $value ? 'true' : 'false');
        }

        if (is_string($value) || null === $value) {
            return $this->appendTextNode($dom, $node, (string)$value);
        }

        return false;
    }

    /**
     * appendTextNode
     *
     * @param \DOMDocument $dom
     * @param mixed $node
     * @param mixed $value
     *
     * @return void
     */
    private function appendTextNode(\DOMDocument $dom, $node, $value)
    {
        if (in_array(strtolower($value), ['true', 'false']) || is_numeric($value)) {
            return $this->createTextNodeWithTypeAttribute($dom, $node, $value, 'string');
        }
        if (preg_match('/(<|>|&)/i', $value)) {
            return $this->createCDATASection($dom, $node, $value);
        }

        return $this->createText($dom, $node, $value);
    }

    /**
     * Appends a dom node to the DOM.
     *
     * @param DOMNode $node
     * @param string  $name
     * @param mixed   $value
     * @param bool $hasAttributes
     *
     * @return void
     */
    private function appendDOMNode(\DOMDocument $dom, $node, $name, $value = null, $hasAttributes = false)
    {
        $element = $dom->createElement($name);

        if ($hasAttributes && $this->nodeValueKey === $name) {
            $this->setElementValue($dom, $node, $value);

            return;
        }

        $this->setElementValue($dom, $element, $value);
        $node->appendChild($element);
    }

    /**
     * Extracts the raw attribute indicator.
     *
     * @param string $key string with leading `@`.
     *
     * @return string
     */
    private function getAttributeName($key)
    {
        if (0 === strpos($key, '@') && $this->isValidNodeName($attrName = substr($key, 1))) {
            return $attrName;
        }

        return null;
    }

    /**
     * Creates a text node on a DOMNode.
     *
     * @param DOMNode $node
     * @param string  $value
     *
     * @return bool
     */
    private function createText(\DOMDocument $dom, \DOMNode $node, $value)
    {
        $text = $dom->createTextNode($value);
        $node->appendChild($text);
    }

    /**
     * Creates a CDATA section node on a DOMNode.
     *
     * @param DOMNode $node
     * @param string  $value
     *
     * @return void
     */
    private function createCDATASection(\DOMDocument $dom, \DOMNode $node, $value)
    {
        $cdata = $dom->createCDATASection($value);
        $node->appendChild($cdata);
    }

    /**
     * Add a value and an associated type attribute to a DOMNode.
     *
     * @param DOMNode $node
     * @param mixed   $value
     * @param string  $type
     *
     * @return void
     */
    private function createTextNodeWithTypeAttribute(\DOMDocument $dom, \DOMNode $node, $value, $type = 'int')
    {
        $text = $dom->createTextNode($value);
        $attr = $dom->createAttribute('type');
        $attr->value = $type;

        $node->appendChild($text);
        $node->appendChild($attr);
    }

    /**
     * getValue
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function getAttributeValue($value, $attrKey = null)
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return is_scalar($value) ? $value : null;
    }
}
