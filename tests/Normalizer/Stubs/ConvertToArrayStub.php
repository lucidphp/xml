<?php

/**
 * This File is part of the Lucid\Xml package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Tests\Normalizer\Stubs;

/**
 * @class ConvertToArrayStub
 */
class ConvertToArrayStub
{
    /**
     * foo
     *
     * @var string
     */
    protected $foo = 'foo';

    /**
     * bar
     *
     * @var string
     */
    protected $bar = 'bar';

    protected $baz;

    public function __construct()
    {
        $this->baz = function () {
        };
    }

    /**
     * getFoo
     *
     * @param mixed $param
     * @access public
     * @return mixed
     */
    public function getBar()
    {
        return $this->bar;
    }

    /**
     * getFoo
     *
     * @param mixed $param
     * @access public
     * @return mixed
     */
    public function getFoo()
    {
        return $this->foo;
    }

    public function getBaz()
    {
        return $this->baz;
    }

    public function getAttributes($param)
    {
        return array('attributes');
    }

    public function setBar($bar)
    {
        $this->bar = $bar;
    }

    public function setFoo($foo)
    {
        $this->foo = $foo;
    }
}
