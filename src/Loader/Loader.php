<?php

/*
 * This File is part of the Lucid\Xml package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Loader;

/**
 * @class Loader
 *
 * @package Lucid\Xml\Loader
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
class Loader implements LoaderInterface
{
    use LoaderTrait;

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function setOption($option, $value)
    {
    }

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function getOption($option, $default = null)
    {
    }

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function load($file, array $options = [])
    {
        if ($this->getDefault($options, self::SIMPLEXML, false)) {
            return $this->loadSimpleXml($file, $options);
        }

        return $this->loadDom($file, $options);
    }
}
