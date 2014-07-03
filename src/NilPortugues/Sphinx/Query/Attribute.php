<?php

namespace NilPortugues\Sphinx\Query;

/**
 * Class Attribute
 * @package NilPortugues\Sphinx\Query
 */
class Attribute
{
    /**
     * Known attribute types.
     */
    const INTEGER = 1;
    const TIMESTAMP = 2;
    const ORDINAL = 3;
    const BOOL = 4;
    const FLOAT = 5;
    const BIGINT = 6;
    const STRING = 7;
    const MULTI = 0x40000001;
    const MULTI64 = 0x40000002;

}
 