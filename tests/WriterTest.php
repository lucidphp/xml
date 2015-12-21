<?php

/*
 * This File is part of the Lucid\Xml package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Tests;

use Lucid\Xml\Writer;
use Lucid\Xml\Normalizer\Normalizer;
use Lucid\Xml\Normalizer\NormalizerInterface;

/**
 * @class WriterTest
 * @package Lucid\Xml
 * @version $Id$
 */
class WriterTest extends \PHPUnit_Framework_TestCase
{

    /** @test */
    public function itShouldBeInstantiable()
    {
        $writer = new Writer($this->mockNormalizer());
        $this->assertInstanceof('\Lucid\Xml\Writer', $writer);

        $w = new Writer(new Normalizer);
        $w->useKeyAsValue('value');
        $args = [
            'foo' => ['@attributes' => ['bar' => 'baz'], 'value' => 'tab']
        ];
    }


    /** @test */
    public function itSouldDumpAnXmlString()
    {
        $writer = new Writer($n = $this->mockNormalizer());

        $n->method('ensureBuildable')->with([])->willReturn([]);
        $xml = $writer->dump([]);

        $this->assertXmlStringEqualsXmlString('<root></root>', $xml);

        $writer = new Writer($n = $this->mockNormalizer());

        $n->method('ensureBuildable')->with($args = ['bar' => 'baz'])->willReturn($args);

        $n->method('normalize')->willReturnCallback(function ($arg) {
            return $arg;
        });

        $xml = $writer->dump($args, 'foo');
        $this->assertXmlStringEqualsXmlString(
            '<foo>
                <bar>baz</bar>
            </foo>',
            $xml
        );
    }

    /** @test */
    public function itShouldWriteToADOMDocument()
    {
        $writer = new Writer($n = $this->mockNormalizer());

        $n->method('ensureBuildable')->with([])->willReturn([]);
        $xml = $writer->writeToDom([]);

        $this->assertInstanceof('DOMDocument', $xml);
    }

    /** @test */
    public function itShouldInflectPlurals()
    {
        $args = [
            'tags' => ['mysql', 'postgres']
        ];

        $writer = new Writer($this->getNormalizerMock());

        $writer->setInflector(function ($value) {
            return strrpos($value, 's') === (strlen($value) - 1) ? substr($value, 0, -1) : $value;
        });

        $xml = $writer->dump($args);
        $this->assertXmlStringEqualsXmlString(
            '<root>
                <tags>
                    <tag>mysql</tag>
                    <tag>postgres</tag>
                </tags>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itShouldMappAttributes()
    {
        $args = [
            'foo' => ['id' => 10, 'val' => 'value']
        ];

        $writer = new Writer($this->getNormalizerMock());

        $writer->addMappedAttribute('foo', 'id');

        $xml = $writer->dump($args);
        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo id="10">
                    <val>value</val>
                </foo>
            </root>',
            $xml
        );

        $writer = new Writer($this->getNormalizerMock());

        $args = [
            'bar' => ['id' => 10, 'val' => 'value']
        ];

        $writer->addMappedAttribute('*', 'id');
        $xml = $writer->dump($args);
        $this->assertXmlStringEqualsXmlString(
            '<root>
                <bar id="10">
                    <val>value</val>
                </bar>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itShouldMappAttributesFromAttributesMap()
    {
        $args = [
            'foo' => ['soma' => true, 'val' => 'value']
        ];

        $writer = new Writer($this->getNormalizerMock());

        $writer->setAttributeMap([
            'foo' => ['soma']
        ]);

        $xml = $writer->dump($args);
        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo soma="true">
                    <val>value</val>
                </foo>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itShouldIgnoreInvalidAttributeContent()
    {
        $args = [
            'foo' => ['id' => [1, 2]]
        ];

        $writer = new Writer($this->getNormalizerMock());

        $writer->setAttributeMap([
          'foo' => ['id']
        ]);

        $xml = $writer->dump($args);

        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo>
                    <id>
                        <item>1</item>
                        <item>2</item>
                    </id>
                </foo>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itShouldAddTypeToStringTypes()
    {
        $args = [
            'foo' => ['value' => '2']
        ];

        $writer = new Writer($this->getNormalizerMock());

        $xml = $writer->dump($args);

        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo>
                    <value type="string">2</value>
                </foo>
            </root>',
            $xml
        );

        $args = [
            'foo' => ['value' => 'true']
        ];

        $xml = $writer->dump($args);

        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo>
                    <value type="string">true</value>
                </foo>
            </root>',
            $xml
        );

        $args = [
            'foo' => ['value' => '<a>link</a>']
        ];

        $xml = $writer->dump($args);

        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo>
                    <value><![CDATA[<a>link</a>]]></value>
                </foo>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itShouldUseValueKeys()
    {
        $args = [
            'foo' => ['@attributes' => ['id' => 10], 'value' => 'value']
        ];

        $writer = new Writer($this->getNormalizerMock());

        $writer->useKeyAsValue('value');

        $xml = $writer->dump($args);
        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo id="10">value</foo>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itShouldUseIndexKeys()
    {
        $args = [
            'foo' => [0, 1]
        ];

        $writer = new Writer($this->getNormalizerMock());

        $xml = $writer->dump($args);

        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo>
                    <item>0</item>
                    <item>1</item>
                </foo>
            </root>',
            $xml
        );

        $writer->useKeyAsIndex('i');

        $xml = $writer->dump($args);

        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo>
                    <i>0</i>
                    <i>1</i>
                </foo>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itShouldShouldUseParentKeyAsIndexIfNoneSpecified()
    {
        $writer = new Writer($this->getNormalizerMock());

        $writer->useKeyAsIndex(null);

        $args = [
            'foo' => [0, 1]
        ];

        $xml = $writer->dump($args);

        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo>0</foo>
                <foo>1</foo>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itShouldConvertXmlElements()
    {
        $args = [
            'foo' => new \DOMElement('bar', 'baz')
        ];

        $writer = new Writer($this->getNormalizerMock());

        $xml = $writer->dump($args);

        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo>
                    <bar>baz</bar>
                </foo>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itShouldNotParseInvalidKeyNames()
    {
        $writer = new Writer($this->getNormalizerMock());

        try {
            $writer->useKeyAsIndex($str = '%%adssad');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame($str . ' is an invalid node name', $e->getMessage());
            return;
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->fail('failed');

    }

    /** @test */
    public function itShouldChangeThisTestName()
    {
        $writer = new Writer($this->getNormalizerMock());

        try {
            $writer->useKeyAsValue($str = '%%adssad');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame($str . ' is an invalid node name', $e->getMessage());
            return;
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->fail('failed');

    }

    /** @test */
    public function itShouldGetCorretBooleans()
    {
        $data = ['foo' => true, 'bar' => false];
        $writer = new Writer($this->getNormalizerMock());

        $xml = $writer->dump($data);

        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo>true</foo>
                <bar>false</bar>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itShouldGetCorrectNumbers()
    {
        $data = ['foo' => 12, 'bar' => 1.2];
        $writer = new Writer($this->getNormalizerMock());

        $xml = $writer->dump($data);

        $this->assertXmlStringEqualsXmlString(
            '<root>
                <foo>12</foo>
                <bar>1.2</bar>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itShouldWriteSimpleValues()
    {
        $writer = new Writer($this->getNormalizerMock());
        $xml = $writer->dump(null);

        $this->assertXmlStringEqualsXmlString('<root></root>', $xml);

        $xml = $writer->dump('foo');

        $this->assertXmlStringEqualsXmlString('<root>foo</root>', $xml);
    }

    /**
     * @test
     * @dataProvider indexedDataProvider
     */
    public function itShouldWriteArrayStructures(array $data, $xml, $valueKey = null)
    {
        $writer = new Writer($this->getNormalizerMock());

        if (null !== $valueKey) {
            $writer->useKeyAsValue($valueKey);
        }

        $this->assertXmlStringEqualsXmlString($xml, $writer->dump($data));
    }

    public function indexedDataProvider()
    {
        return [
            [
                [1, 2, 3, 4],
                '<root><item>1</item><item>2</item><item>3</item><item>4</item></root>'
            ],
            [
                [['nv' => 'bb', '@attrs' => ['index' => '22']]],
                '<root><item index="22">bb</item></root>', 'nv'
            ],
            [
                [['@attrs' => ['index' => '22'], 'value' => 'bb']],
                '<root><item index="22">bb</item></root>', 'value'
            ],
            [
                [33 => 'foo', 1 => 'bar', 0 => 'baz'],
                '<root><item index="33">foo</item><item index="1">bar</item><item index="0">baz</item></root>', 'value'
            ]
        ];
    }

    /** @test */
    public function itShouldParseSimpleXmlObjects()
    {
        $writer = new Writer($this->getNormalizerMock());
        $xml = simplexml_load_string('<foo>bar</foo>');

        $data = ['data' => $xml];

        $xml = $writer->dump($data);
        $this->assertXmlStringEqualsXmlString(
            '<root>
                <data>
                    <foo>bar</foo>
                </data>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itShouldDoWiredStuff()
    {
        $dom = new \DOMDocument;
        $el = $dom->createElement('foo', 'bar');
        $dom->appendChild($el);

        $writer = new Writer($this->getNormalizerMock());

        $data = ['slam' => $dom];

        $xml = $writer->dump($data);
        $this->assertXmlStringEqualsXmlString(
            '<root>
                <slam>
                    <foo>bar</foo>
                </slam>
            </root>',
            $xml
        );
    }

    /** @test */
    public function itIsExpectedItWillIgnoreEmptyNodes()
    {
        $writer = new Writer(new Normalizer);

        $data = ['test' => ['foo' => null]];

        $xml = $writer->dump($data, 'data');

        $this->assertXmlStringEqualsXmlString('<data><test/></data>', $xml);
    }

    protected function getNormalizerMock()
    {
        $n = $this->mockNormalizer();

        $n->method('ensureBuildable')->willReturnCallback(function ($arg) {
            return $arg;
        });

        $n->method('normalize')->willReturnCallback(function ($arg) {
            return $arg;
        });

        return $n;
    }

    private function mockNormalizer()
    {
        return $this->getMockbuilder('\Lucid\Xml\Normalizer\NormalizerInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
