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

use ReflectionObject;
use ReflectionMethod;
use ReflectionProperty;
use Lucid\Common\Helper\Str;
use Lucid\Xml\Traits\XmlHelperTrait;

/**
 * @class Normalizer
 *
 * @package Lucid\Xml
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
class Normalizer implements NormalizerInterface
{
    use XmlHelperTrait;

    /**
     * @var array
     */
    protected $objectCache;

    /**
     * @var array
     */
    protected $ignoredAttributes;

    /**
     * @var array
     */
    protected $ignoredObjects;

    /**
     * @var array
     */
    protected $normalized;

    /**
     * Creates a new Normalizer instance.
     */
    public function __construct()
    {
        $this->normalized = [];
        $this->objectCache = [];
        $this->ignoredObjects = [];
        $this->ignoredAttributes = [];
    }

    /**
     * normalize
     *
     * @param mixed $value
     *
     * @return string
     */
    public function normalize($value)
    {
        $ovalue = $value;

        if (!isset($this->normalized[$value])) {
            $this->normalized[$ovalue] = $this->normalizeString($value);
        }

        return $this->normalized[$ovalue];
    }

    /**
     * ensureArray
     *
     * Will be removed.
     *
     * @param mixed $data
     * @access public
     *
     * @deprecated
     * @return array
     */
    public function ensureArray($data)
    {
        if (!is_array($result = $this->convertValue($data))) {
            return;
        }

        return $result;
    }

    /**
     * ensureBildable
     *
     * @param mixed $data
     * @access public
     * @since v0.1.3
     * @return mixed
     */
    public function ensureBuildable($data)
    {
        if ($this->isXMLElement($data)) {
            return $data;
        }

        if ($this->isTraversable($data)) {
            return $this->recursiveConvertArray($data);
        }

        if (is_object($data)) {
            return $this->convertObject($data) ?: null;
        }

        return $data;
    }

    /**
     * setIgnoredAttributes
     *
     * @access public
     * @return void
     */
    public function setIgnoredAttributes(array $attributes)
    {
        $this->ignoredAttributes = $attributes;
    }

    /**
     * addIgnoredAttribute
     *
     * @param mixed $attribute
     *
     * @access public
     * @return void
     */
    public function addIgnoredAttribute($attribute)
    {
        $this->ignoredAttributes[] = $attribute;
    }

    /**
     * setIgnoredAttributes
     *
     * @param mixed $attributes
     * @access public
     * @return void
     */
    public function setIgnoredObjects(array $classes)
    {
        $this->ignoredObjects = [];

        foreach ($classes as $classname) {
            $this->addIgnoredObject($classname);
        }
    }

    /**
     * addIgnoredObject
     *
     * @param mixed $classname
     * @access public
     * @return void
     */
    public function addIgnoredObject($classname)
    {
        $this->ignoredObjects[] = preg_replace('~^\\\~', '', strtolower($classname));
    }

    /**
     * convertValue
     *
     * @param mixed $data
     *
     * @access protected
     * @return mixed
     */
    protected function convertValue($data)
    {
        if ($this->isTraversable($data)) {
            return $this->recursiveConvertArray($data);
        }

        if (is_object($data)) {
            return $this->convertObject($data) ?: null;
        }

        return $data;
    }

    /**
     * isTraversable
     *
     * @param mixed $data
     * @access protected
     * @return boolean
     */
    protected function isTraversable($data)
    {
        return is_array($data) || $data instanceof \Traversable;
    }

    /**
     * recursiveConvertArray
     *
     * @param array $data
     * @param mixed $ignoreobjects
     * @access protected
     * @return array
     */
    protected function recursiveConvertArray($data)
    {
        $out = [];

        foreach ($data as $key => $value) {

            $nkey = $this->normalize($key);

            if (in_array($nkey, $this->ignoredAttributes)) {
                continue;
            }

            $out[$nkey] = is_scalar($value) ? $value : $this->convertValue($value);

        }

        return $out;
    }

    /**
     * isArrayAble
     *
     * @param  mixed $reflection a reflection object
     * @access protected
     * @return boolean
     */
    protected function isArrayable($data)
    {
        return $data->hasMethod('toArray') && $data->getMethod('toArray')->isPublic();
    }

    /**
     * convertObject
     *
     * @param Object $data
     *
     * @return array
     */
    protected function convertObject($data)
    {

        if ($this->isIgnoredObject($data)) {
            return;
        }

        if ($this->isXMLElement($data)) {
            return $data;
        }

        $reflection = new ReflectionObject($data);

        if ($this->isArrayAble($reflection)) {
            $data = $data->toArray();
            return $this->ensureBuildable($data);
        }

        if ($this->isCircularReference($data)) {
            return;
        }

        $out = [];

        $methods    = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $this->getObjectGetterValues($methods, $data, $out);

        $this->setObjectProperties($properties, $data, $out);

        return $out;
    }

    /**
     * isGetMethod
     *
     * @param mixed $method
     * @access public
     * @return boolean
     */
    protected function isGetMethod(\ReflectionMethod $method)
    {
        return 'get' === substr($method->name, 0, 3) && strlen($method->name) > 3 &&
            0 === $method->getNumberOfRequiredParameters();
    }

    /**
     * getObjectGetterValues
     *
     * @param mixed $methods
     * @param array $out
     * @access protected
     * @return mixed
     */
    protected function getObjectGetterValues($methods, $object, array &$out = [])
    {
        foreach ($methods as $method) {
            $this->setObjectGetterValue($method, $object, $out);
        }
    }
    /**
     * setObjectGetterValue
     *
     * @param ReflectionMethod $method
     * @param array $out
     *
     * @return void
     */
    protected function setObjectGetterValue(ReflectionMethod $method, $object, array &$out = [])
    {
        if (!$this->isGetMethod($method)) {
            return;
        }

        $attributeName  = substr($method->name, 3);
        $attributeValue = $method->invoke($object);

        $nkey = $this->normalize($attributeName);

        if (is_callable($attributeValue) || in_array($nkey, $this->ignoredAttributes)) {
            return;
        }

        if (null !== $attributeValue && !is_scalar($attributeValue)) {
            $attributeValue = $this->ensureBuildable($attributeValue);
        }

        $out[$nkey] = $attributeValue;
    }

    /**
     * Set opbject properties to an output array.
     *
     * @param array $properties
     * @param mixed $data
     * @param array $out
     *
     * @return void
     */
    protected function setObjectProperties(array $properties, $data, array &$out = [])
    {
        foreach ($properties as $property) {
            $prop =  $property->getName();

            if (in_array($name = $this->normalize($prop), $this->ignoredAttributes)) {
                continue;
            }

            $out[$prop] = $this->getObjectPropertyValue($property, $prop, $data);
        }
    }

    /**
     * getPropertyValue
     *
     * @param \ReflectionProperty $property
     * @param mixed $prop
     *
     * @return mixed
     */
    protected function getObjectPropertyValue(\ReflectionProperty $property, $prop, $data)
    {
        $prop =  $property->getName();

        try {
            $value = $data->{$prop};
        } catch (\Exception $e) {
        }

        if (!is_scalar($value)) {
            $value = $this->ensureBuildable($value);
        }

        return $value;
    }

    /**
     * isCircularReference
     *
     * @return boolean
     */
    protected function isCircularReference($data)
    {
        $circularReference = in_array($hash = spl_object_hash($data), $this->objectCache);
        $this->objectCache[] = $hash;

        return $circularReference;
    }

    /**
     * normalizeString
     *
     * @param string $string
     *
     * @return string
     */
    protected function normalizeString($string)
    {
        $value = $this->isAllUpperCase($string) ?
            strtolower(trim($string, '_-#$%')) :
            Str::snakeCase(trim($string, '_-#$%'));

        return strtolower(preg_replace('/[^a-zA-Z0-9(^@)]+/', '-', $value));
    }

    /**
     * isIgnoredObject
     *
     * @param object $oject
     *
     * @return boolean
     */
    protected function isIgnoredObject($object)
    {
        return in_array(
            strtolower($class = ($parent = get_parent_class($object)) ? $parent : get_class($object)),
            $this->ignoredObjects
        );
    }

    /**
     * isAllUpperCase
     *
     * @param string $str
     *
     * @return boolean
     */
    private function isAllUpperCase($str)
    {
        $str = preg_replace('/[^a-zA-Z0-9]/', null, $str);

        return ctype_upper($str);
    }
}
