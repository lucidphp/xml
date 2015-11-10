<?php

/*
 * This File is part of the Lucid\Compiler package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Loader;

use Lucid\Xml\Dom\DOMDocument;
use Lucid\Xml\SimpleXMLElement;
use Lucid\Common\Traits\Getter;

/**
 * @class Loader implements LoaderInterface
 * @see LoaderInterface
 *
 * @package Lucid\Xml\Loader
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class Loader implements LoaderInterface
{
    use Getter;

    /**
     * options
     *
     * @var array
     */
    protected $options;

    protected $defaultOptions;

    /**
     * @access public
     */
    public function __construct(array $options = [])
    {
        $this->resetOptions();

        $this->loadOptions($options);
    }

    /**
     * __clone
     *
     * @access public
     * @return mixed
     */
    public function __clone()
    {
        $this->resetOptions();
    }

    /**
     * setOption
     *
     * @param mixed $option
     * @param mixed $value
     * @access public
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * getOption
     *
     * @param mixed $option
     * @param mixed $default
     * @access public
     * @return mixed
     */
    public function getOption($option = null, $default = null)
    {
        return $this->getDefault($this->options, $option, $default);
    }

    /**
     * load
     *
     * @param mixed $file
     * @access public
     * @return DOMDocument or SimpleXMLElement
     */
    public function load($file, array $options = [])
    {
        $this->loadOptions($options);

        $xml = $this->doLoad($file);

        $this->resotereOptions();

        return $xml;
    }

    /**
     * load
     *
     * @param mixed $file
     * @access public
     * @return DOMDocument|SimpleXMLElement
     */
    protected function doLoad($file)
    {
        $domClass = $this->getOption(static::DOM_CLASS, '\Lucid\Xml\Dom\DOMDocument');

        $dom = new $domClass('1.0', $this->getOption(static::ENCODING, 'UTF-8'));

        $method = $this->getOption(static::FROM_STRING, false) ? 'loadXML' : 'load';

        $this->loadXmlInDom($dom, $file, $method);

        if ($simpleXml = $this->getOption(self::SIMPLEXML, false)) {
            $xml = simplexml_import_dom(
                $dom,
                $this->getOption(
                    static::SIMPLEXML_CLASS,
                    'Lucid\Xml\SimpleXMLElement'
                )
            );
            return $xml;
        }

        return $dom;
    }

    /**
     * loadXmlInDom
     *
     * @param \DOMDocument $dom
     * @param mixed $file
     * @access protected
     * @return DOMDocument;
     */
    private function loadXmlInDom(\DOMDocument $dom, $xml, $method)
    {
        $usedInternalErrors = libxml_use_internal_errors(true);
        $externalEntitiesDisabled = libxml_disable_entity_loader(false);
        libxml_clear_errors();

        if (!$this->loadDom($dom, $xml, $method)) {
            libxml_disable_entity_loader($externalEntitiesDisabled);

            throw new \InvalidArgumentException($this->formatLibXmlErrors($usedInternalErrors));
        }

        // restore previous libxml setting:
        libxml_use_internal_errors($usedInternalErrors);
        libxml_disable_entity_loader($externalEntitiesDisabled);

        return $dom;
    }

    /**
     * loadDom
     *
     * @param \DOMDocument $dom
     * @param mixed $method
     * @param mixed $content
     *
     * @access private
     * @return boolean
     */
    private function loadDom(\DOMDocument $dom, $xml, $method)
    {
        return call_user_func_array(
            [$dom, $method],
            // set LIBXML_NONET to prevent local and remote file inclusion attacks.
            [$xml, LIBXML_NONET | LIBXML_DTDATTR | defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0]
        );
    }

    /**
     * getXmlErrors
     *
     * @access private
     * @return mixed
     */
    private function formatLibXmlErrors($usedInternalErrors)
    {
        $errors = [];

        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf(
                '[%s %s] %s (in file %s in column %s on line %s)',
                LIBXML_ERR_ERROR === $error->level ? 'ERROR' : 'WARNING',
                $error->code,
                $error->message,
                $error->file ?: 'n/a',
                $error->column,
                $error->line
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors($usedInternalErrors);

        return implode("\n", $errors);
    }

    /**
     * loadOptions
     *
     * @param array $options
     *
     * @access private
     * @return mixed
     */
    private function loadOptions(array $options)
    {
        $options = array_merge($this->options, $options);

        foreach ($options as $option => $value) {
            if ($default = $this->getOption($option)) {
                $this->defaultOptions[$option] = $default;
            }

            $this->setOption($option, $value);
        }
    }

    /**
     * resotereOptions
     *
     * @access private
     * @return void
     */
    private function resotereOptions()
    {
        $this->options = $this->defaultOptions;
        $this->defaultOptions = [];
    }

    /**
     * resetOptions
     *
     *
     * @access private
     * @return void
     */
    private function resetOptions()
    {
        $this->options = [];
        $this->defaultOptions = [];
    }
}
