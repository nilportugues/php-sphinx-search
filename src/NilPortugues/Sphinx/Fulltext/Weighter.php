<?php
namespace NilPortugues\Sphinx\Fulltext;

use NilPortugues\Sphinx\SphinxClientException;

/**
 * Class Weighter
 * @package NilPortugues\Sphinx\Fulltext
 */
class Weighter
{
    /**
     * Per-field weights (default is 1 for all fields)
     *
     * @var array
     */
    private $weights = array();

    /**
     * Per-index weights
     *
     * @var array
     */
    private $indexWeights = array();

    /**
     * Per-field-name weights
     *
     * @var array
     */
    private $fieldWeights = array();

    /**
     * DEPRECATED; Throws exception. Use SetFieldWeights() instead.
     *
     * @param  array      $weights
     * @throws \Exception
     */
    public function setWeights(array &$weights)
    {
        unset($weights);
        throw new SphinxClientException("setWeights method is deprecated. Use SetFieldWeights() instead.");

    }

    /**
     * Bind per-field weights by name.
     *
     * @param $weights
     * @return $this
     */
    public function setFieldWeights(array $weights)
    {
        foreach ($weights as &$name => &$weight) {
            $name = (string) $name;
            $weight = (int) $weight;
        }
        $this->fieldWeights = $weights;

        return $this;
    }

    /**
     * Bind per-index weights by name.
     *
     * @param  array $weights
     * @return $this
     */
    public function setIndexWeights(array $weights)
    {
        foreach ($weights as &$index => &$weight) {
            $index = (string) $index;
            $weight = (int) $weight;
        }

        $this->indexWeights = $weights;

        return $this;
    }
}
