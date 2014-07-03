<?php

namespace NilPortugues\Sphinx\Helpers;

/**
 * Class MultiByte
 * @package NilPortugues\Sphinx\Helpers
 */
class MultiByte
{
    /**
     * Enter mbstring workaround mode
     */
    public function push()
    {
        $this->_mbenc = "";
        if (ini_get("mbstring.func_overload") & 2) {
            $this->_mbenc = mb_internal_encoding();
            mb_internal_encoding("latin1");
        }
    }

    /**
     * Leave mbstring workaround mode
     */
    public function pop()
    {
        if ($this->_mbenc)
            mb_internal_encoding($this->_mbenc);
    }
}
 