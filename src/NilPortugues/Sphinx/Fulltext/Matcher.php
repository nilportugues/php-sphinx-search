<?php
namespace NilPortugues\Sphinx\Fulltext;

/**
 * Class Matcher
 * @package NilPortugues\Sphinx\Fulltext
 */
class Matcher
{
    const ALL = 0;
    const ANY = 1;
    const PHRASE = 2;
    const BOOLEAN = 3;
    const EXTENDED = 4;
    const FULLSCAN = 5;
    const EXTENDED2 = 6; // extended engine V2 (TEMPORARY, WILL BE REMOVED)

    /**
     * @param $mode
     * @return bool
     */
    public function isValid($mode)
    {
        return (
            $mode == Matcher::ALL
            || $mode == Matcher::ANY
            || $mode == Matcher::PHRASE
            || $mode == Matcher::BOOLEAN
            || $mode == Matcher::EXTENDED
            || $mode == Matcher::FULLSCAN
            || $mode == Matcher::EXTENDED2
        );
    }
}
 