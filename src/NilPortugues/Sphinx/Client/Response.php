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
     * @var ErrorBag
     */
    private $errorBag;

    /**
     *  Whether $result["matches"] should be a hash or an array
     *
     * @var bool
     */
    private $arrayResult = false;

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @param Connection $connection
     * @param Packer     $packer
     * @param MultiByte  $multiByte
     * @param ErrorBag   $errorBag
     */
    public function __construct(Connection $connection, Packer $packer, MultiByte $multiByte, ErrorBag $errorBag)
    {
        $this->connection = $connection;
        $this->packer = $packer;
        $this->multiByte = $multiByte;
        $this->errorBag = $errorBag;
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

        if ($this->connection->getSocket() === false) {
            fclose($fp);
        }

        // check response
        $read = strlen($response);
        if (!$response || $read != $len) {

            $errorMessage = "received zero-sized searchd response";

            if ($len) {
                $errorMessage = "failed to read searchd response (status=$status, ver=$ver, len=$len, read=$read)";
            }

            $this->errorBag->setError($errorMessage);

            return false;
        }

        // check status
        if ($status == Status::WARNING) {
            list($temp, $wlen) = unpack("N*", substr($response, 0, 4));
            unset($temp);
            $this->errorBag->setWarning(substr($response, 4, $wlen));

            return substr($response, 4 + $wlen);
        }
        if ($status == Status::ERROR) {
            $this->errorBag->setError("searchd error: " . substr($response, 4));

            return false;
        }
        if ($status == Status::RETRY) {
            $this->errorBag->setError("temporary searchd error: " . substr($response, 4));

            return false;
        }
        if ($status != Status::OK) {
            $this->errorBag->setError("unknown status code '$status'");

            return false;
        }

        // check version
        if ($ver < $client_ver) {
            $warning = sprintf(
                "searchd command v.%d.%d older than client's v.%d.%d, some options might not work",
                $ver >> 8,
                $ver & 0xff,
                $client_ver >> 8,
                $client_ver & 0xff
            );

            $this->errorBag->setWarning($warning);
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
        $this->position = 0; // current position
        $max = strlen($response); // max position for checks, to protect against broken responses

        $results = array();
        for ($ires = 0; $ires < $nreqs && $this->position < $max; $ires++) {
            $results[] = array();
            $result =& $results[$ires];

            $result["error"] = "";
            $result["warning"] = "";

            // extract status
            list(, $status) = unpack("N*", substr($response, $this->position, 4));
            $this->position += 4;
            $result["status"] = $status;

            if ($status != Status::OK) {
                list(, $len) = unpack("N*", substr($response, $this->position, 4));
                $this->position += 4;
                $message = substr($response, $this->position, $len);
                $this->position += $len;

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

            list(, $nfields) = unpack("N*", substr($response, $this->position, 4));
            $this->position += 4;

            while ($nfields-- > 0 && $this->position < $max) {
                list(, $len) = unpack("N*", substr($response, $this->position, 4));
                $this->position += 4;
                $fields[] = substr($response, $this->position, $len);
                $this->position += $len;
            }
            $result["fields"] = $fields;

            list(, $nattrs) = unpack("N*", substr($response, $this->position, 4));
            $this->position += 4;

            while ($nattrs-- > 0 && $this->position < $max) {
                list(, $len) = unpack("N*", substr($response, $this->position, 4));
                $this->position += 4;
                $attr = substr($response, $this->position, $len);
                $this->position += $len;
                list(, $type) = unpack("N*", substr($response, $this->position, 4));
                $this->position += 4;
                $attrs[$attr] = $type;
            }
            $result["attrs"] = $attrs;

            // read match count
            list(, $count) = unpack("N*", substr($response, $this->position, 4));
            $this->position += 4;
            list(, $id64) = unpack("N*", substr($response, $this->position, 4));
            $this->position += 4;

            // read matches
            $idx = -1;
            while ($count-- > 0 && $this->position < $max) {
                // index into result array
                $idx++;

                // parse document id and weight
                if ($id64) {
                    $doc = $this->packer->sphUnpackU64(substr($response, $this->position, 8));
                    $this->position += 8;

                    list(, $weight) = unpack("N*", substr($response, $this->position, 4));
                    $this->position += 4;

                } else {
                    list ($doc, $weight) = array_values(unpack("N*N*", substr($response, $this->position, 8)));
                    $this->position += 8;
                    $doc = $this->sphFixUint($doc);
                }

                $weight = sprintf("%u", $weight);

                // create match entry
                if ($this->arrayResult) {
                    $result["matches"][$idx] = array("id" => $doc, "weight" => $weight);
                } else {
                    $result["matches"][$doc]["weight"] = $weight;
                }

                // parse and create attributes
                $attrvals = array();
                foreach ($attrs as $attr => $type) {
                    // handle 64bit ints
                    if ($type == Attribute::BIGINT) {
                        $attrvals[$attr] = $this->packer->sphUnpackI64(substr($response, $this->position, 8));
                        $this->position += 8;
                        continue;
                    }

                    // handle floats
                    if ($type == Attribute::FLOAT) {
                        list(, $uval) = unpack("N*", substr($response, $this->position, 4));
                        $this->position += 4;
                        list(, $fval) = unpack("f*", pack("L", $uval));
                        $attrvals[$attr] = $fval;
                        continue;
                    }

                    // handle everything else as unsigned ints
                    list(, $val) = unpack("N*", substr($response, $this->position, 4));
                    $this->position += 4;

                    if ($type == Attribute::MULTI) {
                        $attrvals[$attr] = array();
                        $nvalues = $val;

                        while ($nvalues-- > 0 && $this->position < $max) {
                            list(, $val) = unpack("N*", substr($response, $this->position, 4));
                            $this->position += 4;
                            $attrvals[$attr][] = $this->sphFixUint($val);
                        }

                    } elseif ($type == Attribute::MULTI64) {

                        $attrvals[$attr] = array();
                        $nvalues = $val;

                        while ($nvalues > 0 && $this->position < $max) {
                            $attrvals[$attr][] = $this->packer->sphUnpackI64(substr($response, $this->position, 8));
                            $this->position += 8;
                            $nvalues -= 2;
                        }

                    } elseif ($type == Attribute::STRING) {
                        $attrvals[$attr] = substr($response, $this->position, $val);
                        $this->position += $val;

                    } else {
                        $attrvals[$attr] = $this->sphFixUint($val);
                    }
                }

                if ($this->arrayResult) {
                    $result["matches"][$idx]["attrs"] = $attrvals;
                } else {
                    $result["matches"][$doc]["attrs"] = $attrvals;
                }
            }

            list ($total, $total_found, $msecs, $words) =  array_values(
                unpack("N*N*N*N*", substr($response, $this->position, 16))
            );

            $result["total"] = sprintf("%u", $total);
            $result["total_found"] = sprintf("%u", $total_found);
            $result["time"] = sprintf("%.3f", $msecs / 1000);
            $this->position += 16;

            while ($words-- > 0 && $this->position < $max) {
                list(, $len) = unpack("N*", substr($response, $this->position, 4));
                $this->position += 4;

                $word = substr($response, $this->position, $len);
                $this->position += $len;

                list ($docs, $hits) = array_values(unpack("N*N*", substr($response, $this->position, 8)));
                $this->position += 8;

                $result["words"][$word] = array("docs" => sprintf("%u", $docs), "hits" => sprintf("%u", $hits));
            }
        }

        $this->multiByte->pop();

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
