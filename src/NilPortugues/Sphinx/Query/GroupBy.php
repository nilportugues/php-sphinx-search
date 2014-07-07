<?php
namespace NilPortugues\Sphinx\Query;
use NilPortugues\Sphinx\SphinxClientException;

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
    const ATTRIBUTE_PAIR = 5;

    /**
     * Group-by attribute name
     *
     * @var string
     */
    private $groupBy = "";

    /**
     * Group-by function (to pre-process group-by attribute value with)
     *
     * @var int
     */
    private $groupFunc = self::DAY;

    /**
     * Group-by sorting clause (to sort groups in result set with)
     *
     * @var string
     */
    private $groupSort = "@group desc";

    /**
     * Group-by count-distinct attribute
     *
     * @var string
     */
    private $groupDistinct = "";

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
            || $func == GroupBy::ATTRIBUTE_PAIR
        );
    }

    /**
     * @param string $groupDistinct
     */
    public function setGroupDistinct($groupDistinct)
    {
        $this->groupDistinct = (string) $groupDistinct;
    }

    /**
     * @return string
     */
    public function getGroupDistinct()
    {
        return $this->groupDistinct;
    }

    /**
     * Clears groupBy settings (for multi-queries)
     *
     * @return $this
     */
    public function reset()
    {
        $this->groupBy = "";
        $this->groupFunc = GroupBy::DAY;
        $this->groupSort = "@group desc";
        $this->groupDistinct = "";

        return $this;
    }

    /**
     * Set grouping attribute and function.
     *
     * @param $attribute
     * @param $function
     * @param string $groupSort
     *
     * @return $this
     * @throws SphinxClientException
     */
    public function setGroupBy($attribute, $function, $groupSort = "@group desc")
    {
        if (!$this->isValid($function)) {
            throw new SphinxClientException('Group By function is not valid');
        }

        $this->groupBy = (string)$attribute;
        $this->groupSort = (string)$groupSort;
        $this->groupFunc = $function;

        return $this;
    }

}
 