<?php
namespace NilPortugues\Sphinx\Fulltext;

use NilPortugues\Sphinx\SphinxClientException;

/**
 * Kept for compatibility issues.
 */
define ("SPH_MATCH_ALL", Matcher::ALL);
define ("SPH_MATCH_ANY", Matcher::ANY);
define ("SPH_MATCH_PHRASE", Matcher::PHRASE);
define ("SPH_MATCH_BOOLEAN", Matcher::BOOLEAN);
define ("SPH_MATCH_EXTENDED", Matcher::EXTENDED);
define ("SPH_MATCH_FULLSCAN", Matcher::FULL_SCAN);
define ("SPH_MATCH_EXTENDED2", Matcher::EXTENDED2);

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
    const FULL_SCAN = 5;
    const EXTENDED2 = 6; // extended engine V2 (TEMPORARY, WILL BE REMOVED)

    /**
     * Query matching mode (default is Matcher::ALL)
     *
     * @var int
     */
    private $mode = self::ALL;

    /**
     * @return int
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Sets a matching mode.
     *
     * @param int $mode
     *
     * @throws SphinxClientException
     * @return $this
     */
    public function setMode($mode)
    {
        if (!$this->isValid($mode)) {
            throw new SphinxClientException('Match mode is not valid');
        }

        $this->mode = (int) $mode;

        return $this;
    }

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
            || $mode == Matcher::FULL_SCAN
            || $mode == Matcher::EXTENDED2
        );
    }
}
