<?php

/*
 * This File is part of the Selene\Module\Xml package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Normalizer;

/**
 * @interface NormalizerInterface
 *
 * @package Selene\Module\Xml
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
interface NormalizerInterface
{
    /**
     * normalize
     *
     * @param string $string
     * @access public
     * @return string
     */
    public function normalize($string);

    /**
     * ensureArray
     *
     * @param mixed $data
     * @access public
     * @return array
     */
    public function ensureArray($data);

    /**
     * ensureBuilable
     *
     * @param mixed $data
     *
     * @access public
     * @return mixed
     */
    public function ensureBuildable($data);
}
