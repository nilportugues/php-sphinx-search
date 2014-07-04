<?php

namespace NilPortugues\Sphinx\Fulltext;

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
}
 