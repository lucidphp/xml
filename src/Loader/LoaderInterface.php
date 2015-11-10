<?php

/*
 * This File is part of the Selene\Module\Xml package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Loader;

/**
 * @interface LoaderInterface
 * @package Selene\Module\Xml
 * @version $Id$
 */
interface LoaderInterface
{
    const ENCODING        = 'encoding';

    const FROM_STRING     = 'from_string';

    const DOM_CLASS       = 'dom_class';

    const SIMPLEXML       = 'simplexml';

    const SIMPLEXML_CLASS = 'simplexml_class';

    public function load($xml);

    public function setOption($option, $value);

    public function getOption($option, $default = null);
}
