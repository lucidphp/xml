<?php

/*
 * This File is part of the Lucid\Xml\Loader package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Loader;

use InvalidArgumentException;
use Lucid\Common\Traits\Getter;

/**
 * @trait LoaderTrait
 *
 * @package Lucid\Xml\Loader
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
trait LoaderTrait
{
    use Getter;

    /**
     * {@inheritdoc}
     */
    public function loadDom($file, array $options = [])
    {
        $class = $this->getDefault($options, static::DOM_CLASS, 'Lucid\Xml\Dom\DOMDocument');

        $this->loadXmlInDom(
            $dom = new $class('1.0', $this->getDefault($options, static::ENCODING, 'UTF-8')),
            $file,
            $this->getDefault($options, static::FROM_STRING, false) ? 'loadXML' : 'load'
        );

        return $dom;
    }

    /**
     * {@inheritdoc}
     */
    public function loadSimpleXml($file, array $options = [])
    {
        return simplexml_import_dom(
            $this->loadDom($file, $options),
            $this->getDefault($options, static::SIMPLEXML_CLASS, 'Lucid\Xml\SimpleXMLElement')
        );
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
        $usedInternalErrors       = libxml_use_internal_errors(true);
        $externalEntitiesDisabled = libxml_disable_entity_loader(false);

        libxml_clear_errors();

        if (!$this->loadDomDocument($dom, $xml, $method)) {
            libxml_disable_entity_loader($externalEntitiesDisabled);

            throw new InvalidArgumentException($this->formatLibXmlErrors($usedInternalErrors));
        }

        // restore previous libxml setting:
        libxml_use_internal_errors($usedInternalErrors);
        libxml_disable_entity_loader($externalEntitiesDisabled);

        return $dom;
    }

    /**
     * loadDomDocument
     *
     * @param \DOMDocument $dom
     * @param mixed $method
     * @param mixed $content
     *
     * @return bool
     */
    private function loadDomDocument(\DOMDocument $dom, $xml, $method)
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
     * @return array
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
}
