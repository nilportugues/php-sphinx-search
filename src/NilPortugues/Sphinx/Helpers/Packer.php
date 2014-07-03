<?php

namespace NilPortugues\Sphinx\Helpers;

/**
 * Class Packer
 * @package NilPortugues\Sphinx\Helpers
 */
class Packer
{
    /**
     * Helper function to pack floats in network byte order.
     *
     * @param $f
     * @return string
     */
    public function _PackFloat($f)
    {
        $t1 = pack("f", $f); // machine order
        list(, $t2) = unpack("L*", $t1); // int in machine order

        return pack("N", $t2);
    }

    /**
     * Pack 64-bit signed.
     *
     * @param $v
     * @return string
     */
    public function sphPackI64($v)
    {
        assert(is_numeric($v));

        // x64
        if (PHP_INT_SIZE >= 8) {
            $v = (int)$v;

            return pack("NN", $v >> 32, $v & 0xFFFFFFFF);
        }

        // x32, int
        if (is_int($v))
            return pack("NN", $v < 0 ? -1 : 0, $v);

        // x32, bcmath
        if (function_exists("bcmul")) {
            if (bccomp($v, 0) == -1)
                $v = bcadd("18446744073709551616", $v);
            $h = bcdiv($v, "4294967296", 0);
            $l = bcmod($v, "4294967296");

            return pack("NN", (float)$h, (float)$l); // conversion to float is intentional; int would lose 31st bit
        }

        // x32, no-bcmath
        $p = max(0, strlen($v) - 13);
        $lo = abs((float)substr($v, $p));
        $hi = abs((float)substr($v, 0, $p));

        $m = $lo + $hi * 1316134912.0; // (10 ^ 13) % (1 << 32) = 1316134912
        $q = floor($m / 4294967296.0);
        $l = $m - ($q * 4294967296.0);
        $h = $hi * 2328.0 + $q; // (10 ^ 13) / (1 << 32) = 2328

        if ($v < 0) {
            if ($l == 0)
                $h = 4294967296.0 - $h;
            else {
                $h = 4294967295.0 - $h;
                $l = 4294967296.0 - $l;
            }
        }

        return pack("NN", $h, $l);
    }

    /**
     * Packs 64-bit unsigned.
     *
     * @param $v
     * @return string
     */
    public function sphPackU64($v)
    {
        assert(is_numeric($v));

        // x64
        if (PHP_INT_SIZE >= 8) {
            assert($v >= 0);

            // x64, int
            if (is_int($v))
                return pack("NN", $v >> 32, $v & 0xFFFFFFFF);

            // x64, bcmath
            if (function_exists("bcmul")) {
                $h = bcdiv($v, 4294967296, 0);
                $l = bcmod($v, 4294967296);

                return pack("NN", $h, $l);
            }

            // x64, no-bcmath
            $p = max(0, strlen($v) - 13);
            $lo = (int)substr($v, $p);
            $hi = (int)substr($v, 0, $p);

            $m = $lo + $hi * 1316134912;
            $l = $m % 4294967296;
            $h = $hi * 2328 + (int)($m / 4294967296);

            return pack("NN", $h, $l);
        }

        // x32, int
        if (is_int($v))
            return pack("NN", 0, $v);

        // x32, bcmath
        if (function_exists("bcmul")) {
            $h = bcdiv($v, "4294967296", 0);
            $l = bcmod($v, "4294967296");

            return pack("NN", (float)$h, (float)$l); // conversion to float is intentional; int would lose 31st bit
        }

        // x32, no-bcmath
        $p = max(0, strlen($v) - 13);
        $lo = (float)substr($v, $p);
        $hi = (float)substr($v, 0, $p);

        $m = $lo + $hi * 1316134912.0;
        $q = floor($m / 4294967296.0);
        $l = $m - ($q * 4294967296.0);
        $h = $hi * 2328.0 + $q;

        return pack("NN", $h, $l);
    }

    /**
     * Unpacks 64-bit unsigned.
     *
     * @param $v
     * @return int|string
     */
    public function sphUnpackU64($v)
    {
        list ($hi, $lo) = array_values(unpack("N*N*", $v));

        if (PHP_INT_SIZE >= 8) {
            if ($hi < 0) $hi += (1 << 32); // because php 5.2.2 to 5.2.5 is totally fucked up again
            if ($lo < 0) $lo += (1 << 32);

            // x64, int
            if ($hi <= 2147483647)
                return ($hi << 32) + $lo;

            // x64, bcmath
            if (function_exists("bcmul"))
                return bcadd($lo, bcmul($hi, "4294967296"));

            // x64, no-bcmath
            $C = 100000;
            $h = ((int)($hi / $C) << 32) + (int)($lo / $C);
            $l = (($hi % $C) << 32) + ($lo % $C);
            if ($l > $C) {
                $h += (int)($l / $C);
                $l = $l % $C;
            }

            if ($h == 0)
                return $l;
            return sprintf("%d%05d", $h, $l);
        }

        // x32, int
        if ($hi == 0) {
            if ($lo > 0)
                return $lo;
            return sprintf("%u", $lo);
        }

        $hi = sprintf("%u", $hi);
        $lo = sprintf("%u", $lo);

        // x32, bcmath
        if (function_exists("bcmul"))
            return bcadd($lo, bcmul($hi, "4294967296"));

        // x32, no-bcmath
        $hi = (float)$hi;
        $lo = (float)$lo;

        $q = floor($hi / 10000000.0);
        $r = $hi - $q * 10000000.0;
        $m = $lo + $r * 4967296.0;
        $mq = floor($m / 10000000.0);
        $l = $m - $mq * 10000000.0;
        $h = $q * 4294967296.0 + $r * 429.0 + $mq;

        $h = sprintf("%.0f", $h);
        $l = sprintf("%07.0f", $l);
        if ($h == "0")
            return sprintf("%.0f", (float)$l);
        return $h . $l;
    }

    /**
     * Unpacks 64-bit signed.
     *
     * @param $v
     * @return int|string
     */
    public function sphUnpackI64($v)
    {
        list ($hi, $lo) = array_values(unpack("N*N*", $v));

        // x64
        if (PHP_INT_SIZE >= 8) {
            if ($hi < 0) $hi += (1 << 32); // because php 5.2.2 to 5.2.5 is totally fucked up again
            if ($lo < 0) $lo += (1 << 32);
            return ($hi << 32) + $lo;
        }

        // x32, int
        if ($hi == 0) {
            if ($lo > 0)
                return $lo;
            return sprintf("%u", $lo);
        } // x32, int
        elseif ($hi == -1) {
            if ($lo < 0)
                return $lo;
            return sprintf("%.0f", $lo - 4294967296.0);
        }

        $neg = "";
        $c = 0;
        if ($hi < 0) {
            $hi = ~$hi;
            $lo = ~$lo;
            $c = 1;
            $neg = "-";
        }

        $hi = sprintf("%u", $hi);
        $lo = sprintf("%u", $lo);

        // x32, bcmath
        if (function_exists("bcmul"))
            return $neg . bcadd(bcadd($lo, bcmul($hi, "4294967296")), $c);

        // x32, no-bcmath
        $hi = (float)$hi;
        $lo = (float)$lo;

        $q = floor($hi / 10000000.0);
        $r = $hi - $q * 10000000.0;
        $m = $lo + $r * 4967296.0;
        $mq = floor($m / 10000000.0);
        $l = $m - $mq * 10000000.0 + $c;
        $h = $q * 4294967296.0 + $r * 429.0 + $mq;
        if ($l == 10000000) {
            $l = 0;
            $h += 1;
        }

        $h = sprintf("%.0f", $h);
        $l = sprintf("%07.0f", $l);
        if ($h == "0")
            return $neg . sprintf("%.0f", (float)$l);
        return $neg . $h . $l;
    }

}
 