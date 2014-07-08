<?php

namespace NilPortugues\Sphinx\Searchd;

/**
 * Kept for compatibility issues.
 */
define ("SEARCHD_OK", Status::OK);
define ("SEARCHD_ERROR", Status::ERROR);
define ("SEARCHD_RETRY", Status::RETRY);
define ("SEARCHD_WARNING", Status::WARNING);

/**
 * Class Status
 * @package NilPortugues\Sphinx\Searchd
 */
class Status
{
    const OK = 0;
    const ERROR = 1;
    const RETRY =  2;
    const WARNING = 3;
}
