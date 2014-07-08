<?php

namespace NilPortugues\Sphinx\Helpers;

/**
 * Class MultiByte
 * @package NilPortugues\Sphinx\Helpers
 */
class MultiByte
{
    /**
     * Stored mbstring encoding
     *
     * @var string
     */
    private $mbstringEncoding = '';

    /**
     * @return string
     */
    public function getMbstringEncoding()
    {
        return $this->mbstringEncoding;
    }

    /**
     * Enter mbstring workaround mode
     */
    public function push()
    {
        $this->mbstringEncoding = "";

        if (ini_get("mbstring.func_overload") & 2) {
            $this->mbstringEncoding = mb_internal_encoding();
            mb_internal_encoding("latin1");
        }
    }

    /**
     * Leave mbstring workaround mode
     */
    public function pop()
    {
        if ($this->mbstringEncoding) {
            mb_internal_encoding($this->mbstringEncoding);
        }
    }
}
