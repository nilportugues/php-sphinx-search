<?php

namespace NilPortugues\Sphinx\Filter;

use NilPortugues\Sphinx\SphinxClientException;

/**
 * Class Filter
 * @package NilPortugues\Sphinx\Filter
 */
class Filter
{
    const VALUES = 0;
    const RANGE = 1;
    const FLOAT_RANGE = 2;

    /**
     * @var array
     */
    private $filters = array();

    /**
     * @var array
     */
    private $anchor = array();

    /**
     * Set values for a set filter. Only matches records where $attribute value is in given set.
     *
     * @param $attribute
     * @param  array $values
     * @param  bool $exclude
     *
     * @return $this
     */
    public function setFilter($attribute, array $values, $exclude = false)
    {
        if (!empty($values)) {
            $exclude = $this->convertToBoolean($exclude);

            if (is_array($values) && count($values)) {

                foreach ($values as $value) {
                    assert(is_numeric($value));
                }

                $this->filters[] = array(
                    "type" => Filter::VALUES,
                    "attr" => (string)$attribute,
                    "exclude" => $exclude,
                    "values" => $values
                );
            }
        }

        return $this;
    }

    /**
     * Converts values to its boolean representation.
     *
     * @param $exclude
     * @return bool
     */
    private function convertToBoolean($exclude)
    {
        if (is_numeric($exclude) && ($exclude == 0 || $exclude == 1)) {
            settype($exclude, 'boolean');

        } elseif ($exclude === true
            || $exclude === false
            || strtolower(trim($exclude)) === 'true'
            || strtolower(trim($exclude)) === 'false'
        ) {
            settype($exclude, 'boolean');
        } else {
            $exclude = false;
        }

        return $exclude;
    }

    /**
     * @param $attribute
     * @param $min
     * @param $max
     * @param bool $exclude
     *
     * @throws SphinxClientException
     * @return $this
     */
    public function setFilterRange($attribute, $min, $max, $exclude = false)
    {
        if ($min > $max) {
            throw new SphinxClientException("Minimum value cannot be greater than Maximum value");
        }

        $this->filters[] = array(
            "type" => Filter::RANGE,
            "attr" => (string)$attribute,
            "min" => (int)$min,
            "max" => (int)$max,
            "exclude" => $this->convertToBoolean($exclude)
        );

        return $this;
    }

    /**
     * Removes a previously set filter.
     * @author: Nil Portugués Calderó
     *
     * @param $attribute
     * @return $this
     */
    public function removeFilter($attribute)
    {
        $attribute = (string) $attribute;

        foreach ($this->filters as $key => $filter) {
            if ($filter['attr'] == $attribute) {
                unset($this->filters[$key]);

                return $this;
            }
        }

        return $this;
    }

    /**
     * Sets float range filter. Only matches records if $attribute value is between $min and $max (inclusive).
     *
     * @param $attribute
     * @param $min
     * @param $max
     * @param  bool $exclude
     *
     * @throws SphinxClientException
     * @return $this
     */
    public function setFilterFloatRange($attribute, $min, $max, $exclude = false)
    {
        if ($min > $max) {
            throw new SphinxClientException("Minimum value cannot be greater than Maximum value");
        }

        $this->filters[] = array(
            "type" => self::FLOAT_RANGE,
            "attr" => (string)$attribute,
            "min" => (float)$min,
            "max" => (float)$max,
            "exclude" => $this->convertToBoolean($exclude)
        );

        return $this;
    }

    /**
     * Clears all filters (for multi-queries).
     *
     * @return $this
     */
    public function reset()
    {
        $this->filters = array();
        $this->anchor = array();

        return $this;
    }
}
 