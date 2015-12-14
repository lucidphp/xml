<?php

/*
 * This File is part of the Lucid\Xml package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Normalizer;

/**
 * @interface NormalizerInterface
 *
 * @package Lucid\Xml
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
     *
     * @return string
     */
    public function normalize($string);

    /**
     * ensureArray
     *
     * @param mixed $data
     *
     * @return array
     */
    public function ensureArray($data);

    /**
     * ensureBuilable
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function ensureBuildable($data);
}
