<?php

namespace NilPortugues\Sphinx\Fulltext;

/**
 * Kept for compatibility issues.
 */
use NilPortugues\Sphinx\SphinxClientException;

define ("SPH_RANK_PROXIMITY_BM25", Ranker::PROXIMITY_BM25);
define ("SPH_RANK_BM25", Ranker::BM25);
define ("SPH_RANK_NONE", Ranker::NONE);
define ("SPH_RANK_WORDCOUNT", Ranker::WORD_COUNT);
define ("SPH_RANK_PROXIMITY", Ranker::PROXIMITY);
define ("SPH_RANK_MATCHANY", Ranker::MATCH_ANY);
define ("SPH_RANK_FIELDMASK", Ranker::FIELD_MASK);
define ("SPH_RANK_SPH04", Ranker::SPH04);
define ("SPH_RANK_EXPR", Ranker::EXPR);
define ("SPH_RANK_TOTAL", Ranker::TOTAL);

/**
 * Class Ranker
 * @package NilPortugues\Sphinx\Fulltext
 */
class Ranker
{
    /**
     * Known ranking modes (ext2 only).
     */
    const PROXIMITY_BM25 = 0;
    const BM25 = 1;
    const NONE = 2;
    const WORD_COUNT = 3;
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

    /**
     * Sets ranking mode.
     *
     * @param $ranker
     * @param string $rankExpr
     *
     * @throws SphinxClientException
     * @return $this
     */
    public function setRankingMode($ranker, $rankExpr = "")
    {
        if (!($ranker === 0 || $ranker >= 1 && $ranker < Ranker::TOTAL)) {
            throw new SphinxClientException('Ranker Mode is invalid');
        }

        $this->setRanker((int) $ranker);
        $this->setRankExpr((string) $rankExpr);

        return $this;
    }
}
