<?php

namespace NilPortugues\Sphinx\Searchd;

/**
 * Kept for compatibility issues.
 */
define ("SEARCHD_COMMAND_SEARCH", Command::SEARCH);
define ("SEARCHD_COMMAND_EXCERPT", Command::EXCERPT);
define ("SEARCHD_COMMAND_UPDATE", Command::UPDATE);
define ("SEARCHD_COMMAND_KEYWORDS", Command::KEYWORDS);
define ("SEARCHD_COMMAND_PERSIST", Command::PERSIST);
define ("SEARCHD_COMMAND_STATUS", Command::STATUS);
define ("SEARCHD_COMMAND_FLUSHATTRS", Command::FLUSH_ATTRS);

/**
 * Class Command
 * @package NilPortugues\Sphinx\Searchd
 */
class Command
{
    const SEARCH = 0;
    const EXCERPT = 1;
    const UPDATE = 2;
    const KEYWORDS = 3;
    const PERSIST = 4;
    const STATUS = 5;
    const FLUSH_ATTRS = 7;

}
