<?php

namespace NilPortugues\Sphinx\Fulltext;

/**
 * Class Ranker
 * @package NilPortugues\Sphinx\Fulltext
 */
class Ranker
{
    /**
     * Known ranking modes (ext2 only).
     */
    const PROXIMITY_BM25 = 0; // default mode, phrase proximity major factor and BM25 minor one
    const BM25 = 1; // statistical mode, BM25 ranking only (faster but worse quality
    const NONE = 2; // no ranking, all matches get a weight of 1
    const WORD_COUNT = 3; // simple word-count weighting, rank is a weighted sum of per-field keyword occurrence counts
    const PROXIMITY = 4;
    const MATCH_ANY = 5;
    const FIELD_MASK = 6;
    const SPH04 = 7;
    const EXPR = 8;
    const TOTAL = 9;

    /**
     * Ranking mode (default is Ranker::PROXIMITY_BM25)
     *
     * @var int
     */
    private $ranker = self::PROXIMITY_BM25;

    /**
     * Ranking mode expression (for Ranker::EXPR)
     *
     * @var string
     */
    private $rankExpr = '';

    /**
     * @param string $rankExpr
     */
    public function setRankExpr($rankExpr)
    {
        $this->rankExpr = $rankExpr;
    }

    /**
     * @return string
     */
    public function getRankExpr()
    {
        return $this->rankExpr;
    }

    /**
     * @param int $ranker
     */
    public function setRanker($ranker)
    {
        $this->ranker = $ranker;
    }

    /**
     * @return int
     */
    public function getRanker()
    {
        return $this->ranker;
    }
}
 