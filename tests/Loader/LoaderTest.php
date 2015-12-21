<?php

/**
 * This File is part of the Lucid\Xml package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Tests\Loader;

use Lucid\Xml\Loader\Loader;

/**
 * @class XmlLoaderTest
 *
 * @package Lucid\Xml
 * @version $Id$
 */
class LoaderTest extends \PHPUnit_Framework_TestCase
{

    /** @test */
    public function itShouldBeInstantiable()
    {
        $this->assertInstanceof('\Lucid\Xml\Loader\LoaderInterface', new Loader);
    }

    /** @test */
    public function itShouldLoadXmlFiles()
    {
        $file = $this->getFixure();

        $loader = new Loader;

        $xml = $loader->load($file);
        $this->assertInstanceof('Lucid\Xml\Dom\DOMDocument', $xml);

        $this->assertInstanceof('DOMDocument', $xml);

        $xml = $loader->loadDom($file);
        $this->assertInstanceof('Lucid\Xml\Dom\DOMDocument', $xml);

        $this->assertInstanceof('DOMDocument', $xml);

        $xml = $loader->load($file, [Loader::SIMPLEXML => true]);
        $this->assertInstanceof('Lucid\Xml\SimpleXMLElement', $xml);

        $xml = $loader->loadSimpleXml($file);
        $this->assertInstanceof('Lucid\Xml\SimpleXMLElement', $xml);
    }

    /** @test */
    public function itShouldLoadXmlStrings()
    {
        $loader = new Loader;

        $xml = $loader->loadDom('<data></data>', [Loader::FROM_STRING => true]);
        $this->assertInstanceOf('DOMDocument', $xml);
    }

    /** @test */
    public function domClassesShouldBeSettable()
    {
        $file = $this->getFixure();

        $loader = new Loader;

        $xml = $loader->loadDom($file, [Loader::DOM_CLASS => 'DOMDocument']);

        $this->assertFalse($xml instanceof \Lucid\Xml\Dom\DOMDocument);
        $this->assertInstanceOf('DOMDocument', $xml);
    }

    /** @test */
    public function simpleXmlClassesShouldBeSettable()
    {
        $file = $this->getFixure();

        $loader = new Loader;

        $xml = $loader->loadSimpleXml($file, [Loader::SIMPLEXML_CLASS => 'SimpleXMLElement']);

        $this->assertFalse($xml instanceof \Lucid\Xml\SimpleXmlElement);
        $this->assertInstanceof('SimpleXMLElement', $xml);
    }

    /** @test */
    public function loadingInvalidXmlShouldThrowExcepton()
    {
        $file = $this->getFixure();

        $loader = new Loader;
        try {
            $loader->loadDom('<data><invalid></data>', [Loader::FROM_STRING => true]);
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
            return;
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->fail('test failed');
    }

    /**
     * get the fixure file
     *
     * @access protected
     * @return string
     */
    private function getFixure()
    {
        return dirname(__DIR__).DIRECTORY_SEPARATOR.'Fixures'.DIRECTORY_SEPARATOR.'test.xml';
    }
}
