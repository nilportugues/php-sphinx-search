<?php

namespace NilPortugues\Sphinx\Searchd;

/**
 * Kept for compatibility issues.
 */
define ("VER_COMMAND_SEARCH", Version::SEARCH);
define ("VER_COMMAND_EXCERPT", Version::EXCERPT);
define ("VER_COMMAND_UPDATE", Version::UPDATE);
define ("VER_COMMAND_KEYWORDS", Version::KEYWORDS);
define ("VER_COMMAND_STATUS", Version::STATUS);
define ("VER_COMMAND_QUERY", Version::QUERY);
define ("VER_COMMAND_FLUSHATTRS", Version::FLUSHATTRS);

/**
 * Class Version
 * @package NilPortugues\Sphinx\Searchd
 */
class Version
{
    const SEARCH = 0x119;
    const EXCERPT = 0x104;
    const UPDATE = 0x102;
    const KEYWORDS = 0x100;
    const STATUS = 0x100;
    const QUERY = 0x100;
    const FLUSHATTRS = 0x100;
}
