<?php

/*
 * This File is part of the Lucid\Xml\Inflector package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Inflector;

/**
 * @interface InflectorInterface
 *
 * @package Lucid\Xml\Inflector
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
interface InflectorInterface
{
    /**
     * pluralize
     *
     * @param string $value
     *
     * @return string
     */
    public function pluralize($value);

    /**
     * singularize
     *
     * @param string $value
     *
     * @return string
     */
    public function singularize($value);
}
