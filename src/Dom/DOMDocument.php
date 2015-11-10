<?php

/*
 * This File is part of the Selene\Module\Xml package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Dom;

use \DOMNode;
use \DOMXpath;
use \DOMDocument as Document;
use \DOMElement as XmlElement;

/**
 * @class DOMDocument extends BaseDom
 * @see BaseDom
 *
 * @package Selene\Module\Xml
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class DOMDocument extends Document
{
    /**
     * xpath
     *
     * @var DOMXpath
     */
    protected $xpath;
    protected $nodeClasses;

    /**
     * Constructor.
     *
     * @param mixed $version
     * @param mixed $encoding
     */
    public function __construct($version = null, $encoding = null)
    {
        $this->nodeClasses = [];
        parent::__construct($version, $encoding);
        $this->registerNodeClass('DOMElement', 'Lucid\Xml\Dom\DOMElement');
    }

    /**
     * {@inheritdoc}
     */
    public function registerNodeClass($baseClass, $extendClass)
    {
        $this->nodeClasses[$a = ltrim($baseClass, '\\')] = ltrim($b = $extendClass, '\\');

        return parent::registerNodeClass($a, $b);
    }

    /**
     * {@inheritdoc}
     */
    public function createElement($name, $content = null)
    {
        return $this->ensureNodeClass(parent::createElement($name, $content));
    }

    /**
     * {@inheritdoc}
     */
    public function createElementNS($namespaceURI, $qualifiedName, $value = null)
    {
        return $this->ensureNodeClass(parent::createElementNS($namespaceURI, $qualifiedName, $value));
    }

    /**
     * xPath
     *
     * @param string $query
     * @param DOMNode $contextNode
     *
     * @return DOMNodeList
     */
    public function xpath($query, DOMNode $contextNode = null)
    {
        return $this->getXpath()->query($query, $contextNode);
    }

    /**
     * {@inheritdoc}
     */
    public function appendDomElement(XmlElement $import, XmlElement $element = null, $deep = true)
    {
        $import = $this->importNode($import, $deep);

        if (null === $element) {
            return $this->firstChild->appendChild($import);
        }

        return $element->appendChild($import);
    }

    /**
     * getXpath
     *
     * @access protected
     * @return DOMXpath
     */
    public function getXpath()
    {
        if (!$this->xpath) {
            $this->xpath = new DOMXpath($this);
        }
        return $this->xpath;
    }

    /**
     * Workarround for hhvm issue
     *
     * @see https://github.com/facebook/hhvm/issues/1848
     *
     * @param DOMNode $node
     *
     * @return DOMNode
     */
    private function ensureNodeClass(DOMNode $node)
    {
        $class = $this->nodeClasses['DOMElement'];

        if (true !== ($node instanceof $class) && $node instanceof \DOMElement) {
            return $node->ownerDocument->importNode($node, true);
        }

        return $node;
    }
}
