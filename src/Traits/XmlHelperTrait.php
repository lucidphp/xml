<?php

/*
 * This File is part of the Lucid\Xml package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */


namespace Lucid\Xml\Traits;

/**
 * @trait XmlHelperTrait
 *
 * @package Lucid\Xml
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 */
trait XmlHelperTrait
{
    /**
     * isXmlElement
     *
     * @param mixed $element
     *
     * @return bool
     */
    public function isXmlElement($element)
    {
        return $element instanceof \DOMNode || $element instanceof \SimpleXmlElement;
    }

    /**
     * valueIsList
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function valueIsList($value)
    {
        return is_array($value) && ctype_digit(implode(array_keys($value)));
    }
}
