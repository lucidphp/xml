<?php

/*
 * This File is part of the Lucid\Xml package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Dom;

use BadMethodCallException;
use DOMElement as XmlElement;

/**
 * @class DOMElement
 *
 * @package Lucid\Xml
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
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

        throw new BadMethodCallException('cannot xpath on element without an owner document');
    }

    /**
     * appendDomElement
     *
     * @param DOMElement $import
     * @access public
     * @return mixed
     */
    public function appendDomElement(XMLElement $import, $deep = true)
    {
        if ($this->ownerDocument) {
            return $this->ownerDocument->appendDomElement($import, $this, $deep);
        }

        throw new BadMethodCallException('cannot add an element without an owner document');
    }
}
