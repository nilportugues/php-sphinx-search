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
    const WORDCOUNT = 3; // simple word-count weighting, rank is a weighted sum of per-field keyword occurrence counts
    const PROXIMITY = 4;
    const MATCHANY = 5;
    const FIELDMASK = 6;
    const SPH04 = 7;
    const EXPR = 8;
    const TOTAL = 9;
}
 