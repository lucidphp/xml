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

use \DOMElement as XmlElement;

/**
 * @class DOMElement extends BaseDOMElement
 * @see BaseDOMElement
 *
 * @package Selene\Module\Xml\Dom
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class DOMElement extends XmlElement
{
    /**
     * xPath
     *
     * @access public
     * @return mixed
     */
    public function xpath($query)
    {
        if ($this->ownerDocument) {
            return $this->ownerDocument->getXpath()->query($query, $this);
        }

        throw new \BadMethodCallException('cannot xpath on element without an owner document');
    }

    /**
     * appendDomElement
     *
     * @param DOMElement $import
     * @access public
     * @return mixed
     */
    public function appendDomElement(\DOMElement $import, $deep = true)
    {
        if ($this->ownerDocument) {
            return $this->ownerDocument->appendDomElement($import, $this, $deep);
        }

        throw new \BadMethodCallException('cannot add an element without an owner document');
    }
}
