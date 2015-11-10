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

use Lucid\Xml\Dom\DOMElement;

/**
 * @interface ParserInterface
 *
 * @package Selene\Module\Xml
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
interface ParserInterface
{
    /**
     * Parses an xml string or file into an array.
     *
     * @param string $xml
     *
     * @return array
     */
    public function parse($xml);

    /**
     * Parses a `\DOMDocument` into an array.
     *
     * @param \DOMDocument $xml
     *
     * @return array
     */
    public function parseDom(\DOMDocument $xml);

    /**
     * Parse the contents of a `DOMElement` to an array.
     *
     * @param DOMElement $xml
     *
     * @return null|array
     */
    public function parseDomElement(DOMElement $param);
}
