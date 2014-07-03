<?php

namespace NilPortugues\Sphinx\Filter;

/**
 * Class Filter
 * @package NilPortugues\Sphinx\Filter
 */
class Filter
{
    /**
     * Known filter types.
     */
    const VALUES = 0;
    const RANGE = 1;
    const FLOATRANGE = 2;

    /**
     * Removes a previously set filter.
     * @author: Nil Portugués Calderó
     *
     * @param $attribute
     * @return SphinxClient
     */
    public function removeFilter($attribute)
    {
        assert(is_string($attribute));
        foreach ($this->_filters as $key => $filter) {
            if ($filter['attr'] == $attribute) {
                unset($this->_filters[$key]);

                return $this;
            }
        }

        return $this;
    }
}
 