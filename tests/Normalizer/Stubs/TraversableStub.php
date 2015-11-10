<?php

/**
 * This File is part of the Normalizer\Stubs package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Tests\Normalizer\Stubs;

/**
 * @class TraversableStub
 * @package Normalizer\Stubs
 * @version $Id$
 */
class TraversableStub implements \IteratorAggregate
{
    protected $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}
