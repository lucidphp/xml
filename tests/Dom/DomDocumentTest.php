<?php

/**
 * This File is part of the Lucid\Xml package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Tests\Dom;

use Lucid\Xml\Dom\DOMElement;
use Lucid\Xml\Dom\DOMDocument;

/**
 * @class DomDocumentTest
 *
 * @package Lucid\Xml
 * @version $Id$
 */
class DomDocumentTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function itShouldBeInstantiable()
    {
        $this->assertInstanceof('\DOMDocument', new DOMDocument);
        $this->assertInstanceof('\Lucid\Xml\Dom\DOMDocument', new DOMDocument);
    }

    /** @test */
    public function itShouldCreateDOMElementsWithRightClass()
    {
        $dom = new DOMDocument;

        $element = $dom->createElement('foo', 'bar');

        $this->assertInstanceof('\Lucid\Xml\Dom\DOMElement', $element);
    }

    /** @test */
    public function itShouldReturnRightClassWhenIteratingOverDomNodeList()
    {
        $xml = '<data><foo>foo</foo><bar>bar</bar></data>';
        $dom = new DOMDocument;

        $dom->loadXML($xml, LIBXML_NONET);

        foreach ($dom->xpath('//foo|//bar') as $node) {
            $this->assertInstanceof('\Lucid\Xml\Dom\DOMElement', $node);
        }
    }

    /** @test */
    public function itShouldAppendElementToAGivenReference()
    {
        $dom = new DOMDocument;

        $root = $dom->createElement('root');

        $firstChild = $dom->createElement('data');
        $nextChild = $dom->createElement('foo', 'bar');

        $dom->appendChild($root);
        $root->appendChild($firstChild);

        $dom->appendDomElement($nextChild, $firstChild);

        $list = $dom->xpath('data/foo');

        $this->assertSame($list->item(0), $nextChild);
    }

    /** @test */
    public function itShouldAppendElementToItsFirstChildIfNoReferenceIsGiven()
    {
        $dom = new DOMDocument;

        $firstChild = $dom->createElement('data');
        $nextChild = $dom->createElement('foo', 'bar');

        $dom->appendChild($firstChild);

        $dom->appendDomElement($nextChild);

        $list = $dom->xpath('foo');

        $this->assertSame($list->item(0), $nextChild);
    }
}
