<?php
namespace NilPortugues\Sphinx\Query;

/**
 * Class GroupBy
 * @package NilPortugues\Sphinx\Query
 */
class GroupBy
{
    const DAY = 0;
    const WEEK = 1;
    const MONTH = 2;
    const YEAR = 3;
    const ATTR = 4;
    const ATTRPAIR = 5;

    /**
     * @param $func
     * @return bool
     */
    public function isValid($func)
    {
        return (
            $func == GroupBy::DAY
            || $func == GroupBy::WEEK
            || $func == GroupBy::MONTH
            || $func == GroupBy::YEAR
            || $func == GroupBy::ATTR
            || $func == GroupBy::ATTRPAIR
        );
    }
}
 