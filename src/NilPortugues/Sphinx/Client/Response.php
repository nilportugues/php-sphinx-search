<?php

namespace NilPortugues\Sphinx\Client;

use NilPortugues\Sphinx\Helpers\MultiByte;
use NilPortugues\Sphinx\Helpers\Packer;
use NilPortugues\Sphinx\Query\Attribute;
use NilPortugues\Sphinx\Searchd\Status;

/**
 * Class Response
 * @package NilPortugues\Sphinx\Client
 */
class Response
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Packer
     */
    private $packer;

    /**
     * @var MultiByte
     */
    private $multiByte;

    /**
     * @param Connection $connection
     * @param Packer $packer
     * @param MultiByte $multiByte
     */
    public function __construct(Connection $connection, Packer $packer, MultiByte $multiByte)
    {
        $this->connection = $connection;
        $this->packer = $packer;
        $this->multiByte = $multiByte;
    }

    /**
     * Get and check response packet from searchd server.
     *
     * @param $fp
     * @param $client_ver
     * @return bool|string
     */
    public function getResponse($fp, $client_ver)
    {
        $status = '';
        $response = "";
        $len = 0;
        $ver = '';

        $header = fread($fp, 8);
        if (strlen($header) == 8) {
            list ($status, $ver, $len) = array_values(unpack("n2a/Nb", $header));
            $left = $len;
            while ($left > 0 && !feof($fp)) {
                $chunk = fread($fp, min(8192, $left));
                if ($chunk) {
                    $response .= $chunk;
                    $left -= strlen($chunk);
                }
            }
        }

        if ($this->socket === false)
            fclose($fp);

        // check response
        $read = strlen($response);
        if (!$response || $read != $len) {
            $this->error = $len
                ? "failed to read searchd response (status=$status, ver=$ver, len=$len, read=$read)"
                : "received zero-sized searchd response";

            return false;
        }

        // check status
        if ($status == Status::WARNING) {
            list($temp, $wlen) = unpack("N*", substr($response, 0, 4));
            unset($temp);
            $this->warning = substr($response, 4, $wlen);

            return substr($response, 4 + $wlen);
        }
        if ($status == Status::ERROR) {
            $this->error = "searchd error: " . substr($response, 4);

            return false;
        }
        if ($status == Status::RETRY) {
            $this->error = "temporary searchd error: " . substr($response, 4);

            return false;
        }
        if ($status != Status::OK) {
            $this->error = "unknown status code '$status'";

            return false;
        }

        // check version
        if ($ver < $client_ver) {
            $this->warning = sprintf(
                "searchd command v.%d.%d older than client's v.%d.%d, some options might not work",
                $ver >> 8,
                $ver & 0xff,
                $client_ver >> 8,
                $client_ver & 0xff
            );
        }

        return $response;
    }


    /**
     * Helper function that parses and returns search query (or queries) response
     * @param $response
     * @param $nreqs
     * @return array
     */
    public function parseSearchResponse($response, $nreqs)
    {
        $p = 0; // current position
        $max = strlen($response); // max position for checks, to protect against broken responses

        $results = array();
        for ($ires = 0; $ires < $nreqs && $p < $max; $ires++) {
            $results[] = array();
            $result =& $results[$ires];

            $result["error"] = "";
            $result["warning"] = "";

            // extract status
            list(, $status) = unpack("N*", substr($response, $p, 4));
            $p += 4;
            $result["status"] = $status;

            if ($status != Status::OK) {
                list(, $len) = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $message = substr($response, $p, $len);
                $p += $len;

                if ($status == Status::WARNING) {
                    $result["warning"] = $message;
                } else {
                    $result["error"] = $message;
                    continue;
                }
            }

            // read schema
            $fields = array();
            $attrs = array();

            list(, $nfields) = unpack("N*", substr($response, $p, 4));
            $p += 4;

            while ($nfields-- > 0 && $p < $max) {
                list(, $len) = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $fields[] = substr($response, $p, $len);
                $p += $len;
            }
            $result["fields"] = $fields;

            list(, $nattrs) = unpack("N*", substr($response, $p, 4));
            $p += 4;

            while ($nattrs-- > 0 && $p < $max) {
                list(, $len) = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $attr = substr($response, $p, $len);
                $p += $len;
                list(, $type) = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $attrs[$attr] = $type;
            }
            $result["attrs"] = $attrs;

            // read match count
            list(, $count) = unpack("N*", substr($response, $p, 4));
            $p += 4;
            list(, $id64) = unpack("N*", substr($response, $p, 4));
            $p += 4;

            // read matches
            $idx = -1;
            while ($count-- > 0 && $p < $max) {
                // index into result array
                $idx++;

                // parse document id and weight
                if ($id64) {
                    $doc = $this->packer->sphUnpackU64(substr($response, $p, 8));
                    $p += 8;

                    list(, $weight) = unpack("N*", substr($response, $p, 4));
                    $p += 4;

                } else {
                    list ($doc, $weight) = array_values(unpack("N*N*",
                        substr($response, $p, 8)));
                    $p += 8;
                    $doc = $this->sphFixUint($doc);
                }

                $weight = sprintf("%u", $weight);

                // create match entry
                if ($this->arrayResult)
                    $result["matches"][$idx] = array("id" => $doc, "weight" => $weight);
                else
                    $result["matches"][$doc]["weight"] = $weight;

                // parse and create attributes
                $attrvals = array();
                foreach ($attrs as $attr => $type) {
                    // handle 64bit ints
                    if ($type == Attribute::BIGINT) {
                        $attrvals[$attr] = $this->packer->sphUnpackI64(substr($response, $p, 8));
                        $p += 8;
                        continue;
                    }

                    // handle floats
                    if ($type == Attribute::FLOAT) {
                        list(, $uval) = unpack("N*", substr($response, $p, 4));
                        $p += 4;
                        list(, $fval) = unpack("f*", pack("L", $uval));
                        $attrvals[$attr] = $fval;
                        continue;
                    }

                    // handle everything else as unsigned ints
                    list(, $val) = unpack("N*", substr($response, $p, 4));
                    $p += 4;

                    if ($type == Attribute::MULTI) {
                        $attrvals[$attr] = array();
                        $nvalues = $val;

                        while ($nvalues-- > 0 && $p < $max) {
                            list(, $val) = unpack("N*", substr($response, $p, 4));
                            $p += 4;
                            $attrvals[$attr][] = $this->sphFixUint($val);
                        }

                    } elseif ($type == Attribute::MULTI64) {

                        $attrvals[$attr] = array();
                        $nvalues = $val;

                        while ($nvalues > 0 && $p < $max) {
                            $attrvals[$attr][] = $this->packer->sphUnpackI64(substr($response, $p, 8));
                            $p += 8;
                            $nvalues -= 2;
                        }

                    } elseif ($type == Attribute::STRING) {
                        $attrvals[$attr] = substr($response, $p, $val);
                        $p += $val;

                    } else {
                        $attrvals[$attr] = $this->sphFixUint($val);
                    }
                }

                if ($this->arrayResult) {
                    $result["matches"][$idx]["attrs"] = $attrvals;
                }  else {
                    $result["matches"][$doc]["attrs"] = $attrvals;
                }
            }

            list ($total, $total_found, $msecs, $words) =  array_values(unpack("N*N*N*N*", substr($response, $p, 16)));

            $result["total"] = sprintf("%u", $total);
            $result["total_found"] = sprintf("%u", $total_found);
            $result["time"] = sprintf("%.3f", $msecs / 1000);
            $p += 16;

            while ($words-- > 0 && $p < $max) {
                list(, $len) = unpack("N*", substr($response, $p, 4));
                $p += 4;

                $word = substr($response, $p, $len);
                $p += $len;

                list ($docs, $hits) = array_values(unpack("N*N*", substr($response, $p, 8)));
                $p += 8;

                $result["words"][$word] = array("docs" => sprintf("%u", $docs), "hits" => sprintf("%u", $hits));
            }
        }

        $this->multiByte->Pop();

        return $results;
    }

    /**
     * @param $value
     * @return int|string
     */
    public function sphFixUint($value)
    {
        if (PHP_INT_SIZE >= 8) {
            if ($value < 0) {
                $value += (1 << 32);
            }
        } else {
            $value = sprintf("%u", $value);
        }

        return $value;
    }
}
 