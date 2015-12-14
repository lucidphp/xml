<?php

/*
 * This File is part of the Lucid\Xml package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Loader;

/**
 * @interface LoaderInterface
 *
 * @package Lucid\Xml
 * @version $Id$
 */
interface LoaderInterface
{
    /** @var string */
    const ENCODING        = 'encoding';

    /** @var string */
    const FROM_STRING     = 'from_string';

    /** @var string */
    const DOM_CLASS       = 'dom_class';

    /** @var string */
    const SIMPLEXML       = 'simplexml';

    /** @var string */
    const SIMPLEXML_CLASS = 'simplexml_class';

    /**
     * load
     *
     * @param string $xml
     *
     * @return DOMDocument or SimpleXMLElement
     */
    public function load($xml);

    /**
     * setOption
     *
     * @param string $option
     * @param mixed $value
     *
     * @return void
     */
    public function setOption($option, $value);

    /**
     * getOption
     *
     * @param string $option
     * @param mixed $default
     *
     * @return mixed
     */
    public function getOption($option, $default = null);
}
