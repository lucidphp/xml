<?php

/*
 * This File is part of the Selene\Module\Xml package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml;

use Lucid\Xml\DOM\DOMElement;
use Lucid\Xml\DOM\DOMDocument;
use Lucid\Xml\Traits\XmlHelperTrait;
use Lucid\Xml\Normalizer\Normalizer;
use Lucid\Xml\Normalizer\NormalizerInterface;

/**
 * @class Writer
 *
 * @package Selene\Module\Xml
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class XmlWriter
{
    use XmlHelperTrait;

    /**
     * dom
     *
     * @var mixed
     */
    protected $dom;

    /**
     * encoding
     *
     * @var string
     */
    protected $encoding;

    /**
     * normalizer
     *
     * @var NormalizerInterface
     */
    protected $normalizer;

    /**
     * inflector
     *
     * @var callable
     */
    protected $inflector;

    /**
     * attributemap
     *
     * @var mixed
     */
    protected $attributemap;

    /**
     * nodeValueKey
     *
     * @var string
     */
    protected $nodeValueKey;

    /**
     * indexKey
     *
     * @var mixed
     */
    protected $indexKey;

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
     * setEncoding
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
     * getEncoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * setNormalizer
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
     * getNormalizer
     *
     * @return Thapp\XmlBuilder\NormalizerInterface
     */
    public function getNormalizer()
    {
        return $this->normalizer;
    }

    /**
     * setInflector
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
     * setAttributeMapp
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
     * addMappedAttribute
     *
     * @param mixed $nodeName
     * @param mixed $attribute
     *
     * @return void
     */
    public function addMappedAttribute($nodeName, $attribute)
    {
        $this->attributemap[$nodeName][] = $attribute;
    }

    /**
     * isMappedAttribute
     *
     * @param mixed $name
     * @param mixed $key
     *
     * @return boolean
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
     * useKeyAsValue
     *
     * @param mixed $key
     *
     * @throws \InvalidArgumentException if $key is not null and an invalid nodename.
     * @return void
     */
    public function useKeyAsValue($key, $normalize = false)
    {
        if (true !== $normalize && !$this->isValidNodeName($key)) {
            throw new \InvalidArgumentException(sprintf('%s is an invalid node name', $key));
        }

        $this->nodeValueKey = $this->normalizer->normalize($key);
    }

    /**
     * setIndexKey
     *
     * @param string $key
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

        $this->indexKey = $this->normalizer->normalize($key);
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
        $normalizer = $this->getNormalizer();
        $hasAttributes = false;

        foreach ($data as $key => $value) {

            if (!is_scalar($value) && !($value = $normalizer->ensureBuildable($value))) {
                continue;
            }

            if ($this->mapAttributes($node, $normalizer->normalize($key), $value)) {
                $hasAttributes = true;
                continue;
            }

            // set the default index key if there's no other way:
            if (is_int($key) || !$this->isValidNodeName($key)) {
                $key = $this->indexKey;
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
     * @return boolean
     */
    protected function appendDomList(NormalizerInterface $normalizer, \DOMDocument $dom, $node, $key, $value)
    {
        // check for integer keys
        if (!ctype_digit(implode('', array_keys($value)))) {
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
     * @return boolean
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
     * @return boolean
     */
    private function mapAttributes(\DOMNode $node, $key, $value)
    {
        if ($attrName = $this->getAttributeName($node, $key)) {

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
     * @return null|boolean returns false if no value was set, else null;
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
     * @param boolean $hasAttributes
     *
     * @return void
     */
    private function appendDOMNode(\DOMDocument $dom, $node, $name, $value = null, $hasAttributes = false)
    {
        $element = $dom->createElement($name);

        if ($hasAttributes && $this->nodeValueKey === $name) {
            return $this->setElementValue($dom, $node, $value);
        }

        $this->setElementValue($dom, $element, $value);
        $node->appendChild($element);
    }

    /**
     * getAttributeName
     *
     * @param DOMNode $parent
     * @param mixed $key
     *
     * @return string|boolean
     */
    private function getAttributeName(\DOMNode $parent, $key)
    {
        if (0 === strpos($key, '@') && $this->isValidNodeName($attrName = substr($key, 1))) {
            return $attrName;
        }

        return false;
    }

    /**
     * Create a text node on a DOMNode.
     *
     * @param DOMNode $node
     * @param string  $value
     *
     * @return boolean
     */
    private function createText(\DOMDocument $dom, \DOMNode $node, $value)
    {
        $text = $dom->createTextNode($value);
        $node->appendChild($text);
    }

    /**
     * Create a CDATA section node on a DOMNode.
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
