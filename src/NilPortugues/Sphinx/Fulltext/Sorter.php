<?php

namespace NilPortugues\Sphinx\Fulltext;
use NilPortugues\Sphinx\SphinxClientException;

/**
 * Class Sorter
 * @package NilPortugues\Sphinx\Fulltext
 */
class Sorter
{
    /**
     * Known sort modes.
     */
    const RELEVANCE = 0;
    const ATTR_DESC = 1;
    const ATTR_ASC = 2;
    const TIME_SEGMENTS = 3;
    const EXTENDED = 4;
    const EXPR = 5;


    /**
     * @var int
     */
    private $sort = self::RELEVANCE;

    /**
     * Attribute to sort by (default is "")
     *
     * @var string
     */
    private $sortBy = '';

    /**
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return int
     */
    public function getSortBy()
    {
        return $this->sortBy;
    }

    /**
     * @param $mode
     * @return bool
     */
    public function isValid($mode)
    {
        return (
            $mode == Sorter::RELEVANCE
            || $mode == Sorter::ATTR_DESC
            || $mode == Sorter::ATTR_ASC
            || $mode == Sorter::TIME_SEGMENTS
            || $mode == Sorter::EXTENDED
            || $mode == Sorter::EXPR
        );
    }

    /**
     * Set matches sorting mode.
     *
     * @param $mode
     * @param string $sortBy
     *
     * @return $this
     * @throws SphinxClientException
     */
    public function setSortMode($mode, $sortBy = "")
    {
        if (!$this->isValid($mode) || !($mode == Sorter::RELEVANCE || strlen($sortBy) > 0)) {
            throw new SphinxClientException('Sorting mode is not valid');
        }

        $this->sort = (int)$mode;
        $this->sortBy = (string)$sortBy;

        return $this;
    }
}
 