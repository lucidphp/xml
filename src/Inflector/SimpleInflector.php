<?php

/*
 * This File is part of the Lucid\Xml package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Xml\Inflector;

/**
 * This class helps for handling Xml conversions of pluralized/singularaized
 * node sets.
 *
 * It is not suiteable for inflecting strings in general.
 *
 * @class SimleInflector
 *
 * @package Lucid\Xml
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 */
class SimpleInflector implements InflectorInterface
{
    /** @var string */
    const APPEND = '+';

    /** @var string */
    const REDUCE = '-';

    /** @var array */
    protected $cache;

    /** @var bool */
    protected $truncate;

    /** @var array */
    protected $singulars;

    /** @var array */
    protected $plurals;

    /**
     * Pattern for 'y' to 'ies' and 'as/ox' to 'ases/oxes' conversion.
     *
     * @var array
     */
    protected static $splurals = [
        '~(\w+[^y])y$~i' => '$1ies',
        '~(\w+(as|ox))$~i' => '$1es',
    ];

    /**
     * Pattern for 'ies' to 'y' and 'ases/oxes' to 'as/ox' conversion.
     *
     * @var array
     */
    protected static $ssingulars = [
        '~(\w+[^ies])ies$~i' => '$1y',
        '~(\w+(ox|as))(es)$~i' => '$1',
    ];

    /**
     * Constructor.
     *
     * @param boolean $truncate will reduce or append trailing `s` if pattern
     * match is unsuccessful.
     * @param array   $singulars additional pattern
     * @param array   $plurals   additional pattern
     */
    public function __construct($truncate = false, array $singulars = [], array $plurals = [])
    {
        $this->truncate = (bool)$truncate;
        $this->cache = ['s' => [], 'p' => []];

        $this->setSingulars($singulars);
        $this->setPlurals($plurals);
    }

    /**
     * pluralize
     *
     * @param string $value
     *
     * @return string
     */
    public function pluralize($value)
    {
        $key = $this->truncate ? self::APPEND : '_';

        return $this->getValue($value, $key, $this->plurals, $this->cache['p'], $this->truncate);
    }

    /**
     * singularize
     *
     * @param string $value
     *
     * @return string
     */
    public function singularize($value)
    {

        $key = $this->truncate ? self::REDUCE : '_';

        return $this->getValue($value, $key, $this->singulars, $this->cache['s'], $this->truncate);
    }

    /**
     * getValue
     *
     * @param string   $value
     * @param string   $key
     * @param array    $patterns
     * @param array    $cache
     * @param boolean  $truncate
     *
     * @return string
     */
    protected function getValue($value, $key, array $patterns, array &$cache = [], $truncate = false)
    {
        if (null === $value || !strlen($value)) {
            return $value;
        }

        if (!isset($cache[$key][$value])) {
            $cache[$key][$value] = $this->inflect($patterns, $value, $truncate ? $key : null);
        }

        return $cache[$key][$value];
    }

    /**
     * inflect
     *
     * @param array $plurals
     * @param string $value
     * @param boolran $rdc
     *
     * @return string
     */
    protected function inflect(array $plurals, $value, $rdc = null)
    {
        foreach ($plurals as $pattern => $repl) {
            if (preg_match($pattern, $value)) {
                return preg_replace($pattern, $repl, $value);
            }
        }

        return null === $rdc ? $value : (self::REDUCE === $rdc ? $this->reduceVal($value) : $this->appendVal($value));
    }

    /**
     * reduceVal
     *
     * @param string $val
     *
     * @return string
     */
    protected function reduceVal($val)
    {
        return 's' === substr($val, -1) ? substr($val, 0, -1) : $val;
    }

    /**
     * appendVal
     *
     * @param string $val
     *
     * @return string
     */
    protected function appendVal($val)
    {
        return 's' === substr($val, -1) ? $val : $val . 's';
    }

    /**
     * setSingulars
     *
     * @param array $singulars
     *
     * @return void
     */
    protected function setSingulars(array $singulars)
    {
        $this->singulars = array_merge(static::$ssingulars, $singulars);
    }

    /**
     * setPlurals
     *
     * @param array $plurals
     *
     * @return void
     */
    protected function setPlurals(array $plurals)
    {
        $this->plurals = array_merge(static::$splurals, $plurals);
    }
}
