<?php
/**
 * @author: Andrew Aksyonoff
 * Copyright (c) 2001-2012, Andrew Aksyonoff
 * Copyright (c) 2008-2012, Sphinx Technologies Inc
 * All rights reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License. You should have
 * received a copy of the GPL license along with this program; if you
 * did not, you can find it at http://www.gnu.org/
 */

namespace NilPortugues\Sphinx;

use NilPortugues\Sphinx\Helpers\MultiByte;
use NilPortugues\Sphinx\Helpers\Packer;
use NilPortugues\Sphinx\Filter\Filter;
use NilPortugues\Sphinx\Fulltext\Matcher;
use NilPortugues\Sphinx\Fulltext\Sorter;
use NilPortugues\Sphinx\Fulltext\Ranker;
use NilPortugues\Sphinx\Query\Attribute;
use NilPortugues\Sphinx\Query\GroupBy;
use NilPortugues\Sphinx\Searchd\Status;
use NilPortugues\Sphinx\Searchd\Command;
use NilPortugues\Sphinx\Searchd\Version;

/**
 * PHP version of Sphinx searchd client.
 *
 * @author Andrew Aksyonoff <andrew.aksyonoff@gmail.com>
 */
class SphinxClient
{
    private $host; // searchd host (default is "localhost")
    private $port; // searchd port (default is 9312)
    private $offset; // how many records to seek from result-set start (default is 0)
    private $_limit; // how many records to return from result-set starting at offset (default is 20)
    private $_mode; // query matching mode (default is Matcher::ALL)
    private $_weights; // per-field weights (default is 1 for all fields)
    private $_sort; // match sorting mode (default is Sorter::RELEVANCE)
    private $sortBy; // attribute to sort by (defualt is "")
    private $_min_id; // min ID to match (default is 0, which means no limit)
    private $_max_id; // max ID to match (default is 0, which means no limit)
    private $_filters; // search filters
    private $groupBy; // group-by attribute name
    private $groupFunc; // group-by function (to pre-process group-by attribute value with)
    private $groupSort; // group-by sorting clause (to sort groups in result set with)
    private $groupDistinct; // group-by count-distinct attribute
    private $_maxmatches; // max matches to retrieve
    private $_cutoff; // cutoff to stop searching at (default is 0)
    private $_retrycount; // distributed retries count
    private $retryDelay; // distributed retries delay
    private $_anchor; // geographical anchor point
    private $indexWeights; // per-index weights
    private $_ranker; // ranking mode (default is Ranker::PROXIMITY_BM25)
    private $_rankexpr; // ranking mode expression (for Ranker::EXPR)
    private $_maxquerytime; // max query time, milliseconds (default is 0, do not limit)
    private $_fieldweights; // per-field-name weights
    private $_overrides; // per-query attribute values overrides
    private $_select; // select-list (attributes or expressions, with optional aliases)
    private $_error; // last error message
    private $_warning; // last warning message
    private $_connerror; // connection error vs remote error flag
    private $_reqs; // requests array for multi-query
    private $_mbenc; // stored mbstring encoding
    private $arrayResult; // whether $result["matches"] should be a hash or an array
    private $_timeout; // connect timeout
    private $_path;
    private $_socket;

    /**
     * @var Packer
     */
    private $packer;

    /**
     * @var MultiByte
     */
    private $multiByte;

    /**
     * Creates a new client object and fill defaults
     */
    public function __construct()
    {
        $this->packer = new Packer();
        $this->multiByte = new MultiByte();

        // Per-client-object settings
        $this->host = "localhost";
        $this->port = 9312;
        $this->_path = false;
        $this->_socket = false;

        // Per-query settings
        $this->offset = 0;
        $this->_limit = 20;

        $this->_mode = Matcher::ALL;

        $this->_weights = array();
        $this->_fieldweights = array();
        $this->indexWeights = array();

        $this->_sort = Sorter::RELEVANCE;
        $this->sortBy = "";

        $this->_min_id = 0;
        $this->_max_id = 0;

        $this->_filters = array();

        $this->groupBy = "";
        $this->groupFunc = GroupBy::DAY;
        $this->groupSort = "@group desc";

        $this->groupDistinct = "";
        $this->_maxmatches = 1000;
        $this->_cutoff = 0;
        $this->_retrycount = 0;
        $this->retryDelay = 0;
        $this->_anchor = array();


        $this->_ranker = Ranker::PROXIMITY_BM25;
        $this->_rankexpr = "";

        $this->_maxquerytime = 0;

        $this->_overrides = array();
        $this->_select = "*";

        // Per-reply fields (for single-query case)
        $this->_error = "";
        $this->_warning = "";
        $this->_connerror = false;

        // Requests storage (for multi-query case)
        $this->_reqs = array();
        $this->_mbenc = "";
        $this->arrayResult = false;
        $this->_timeout = 0;
    }

    /**
     * Closes Sphinx socket.
     */
    public function __destruct()
    {
        if ($this->_socket !== false) {
            fclose($this->_socket);
        }
    }

    /**
     * Gets last error message.
     *
     * @return string
     */
    public function getLastError()
    {
        return $this->_error;
    }

    /**
     * Gets last warning message.
     *
     * @return string
     */
    public function getLastWarning()
    {
        return $this->_warning;
    }

    /**
     * Get last error flag. It's thought to be used to inform of network connection errors from searchd errors or broken responses.
     *
     * @return boolean
     */
    public function isConnectError()
    {
        return $this->_connerror;
    }

    /**
     * Sets the searchd host name and port.
     *
     * @param $host
     * @param  int $port
     * @return SphinxClient
     */
    public function setServer($host, $port = 0)
    {
        assert(is_string($host));
        if ($host[0] == '/') {
            $this->_path = 'unix://' . $host;
        }
        if (substr($host, 0, 7) == "unix://") {
            $this->_path = $host;
        }

        $this->host = $host;
        if (is_int($port))
            if ($port)
                $this->port = $port;
        $this->_path = '';

        return $this;
    }

    /**
     * Sets the server connection timeout (0 to remove).
     *
     * @param $timeout
     * @return SphinxClient
     */
    public function setConnectTimeout($timeout)
    {
        assert(is_int($timeout));
        assert($timeout >= 0);
        $this->_timeout = $timeout;

        return $this;
    }

    /**
     * Sets an offset and count into result set, and optionally set max-matches and cutoff limits.
     *
     * @param $offset
     * @param $limit
     * @param  int $max
     * @param  int $cutoff
     * @return SphinxClient
     */
    public function setLimits($offset, $limit, $max = 0, $cutoff = 0)
    {
        assert(is_int($offset));
        assert(is_int($limit));
        assert(is_int($max));
        assert(is_int($cutoff));
        assert($offset >= 0);
        assert($limit > 0);
        assert($max >= 0);
        assert($cutoff >= 0);

        $this->offset = $offset;
        $this->_limit = $limit;
        if ($max > 0)
            $this->_maxmatches = $max;
        if ($cutoff > 0)
            $this->_cutoff = $cutoff;

        return $this;
    }

    /**
     * Set maximum query time, in milliseconds, per-index.Integer, 0 means "do not limit".
     *
     * @param $max
     * @return SphinxClient
     */
    public function setMaxQueryTime($max)
    {
        assert(is_int($max));
        assert($max >= 0);
        $this->_maxquerytime = $max;

        return $this;
    }

    /**
     * Sets a matching mode.
     *
     * @param $mode
     * @return SphinxClient
     */
    public function setMatchMode($mode)
    {
        assert($mode == Matcher::ALL
            || $mode == Matcher::ANY
            || $mode == Matcher::PHRASE
            || $mode == Matcher::BOOLEAN
            || $mode == Matcher::EXTENDED
            || $mode == Matcher::FULLSCAN
            || $mode == Matcher::EXTENDED2);
        $this->_mode = $mode;

        return $this;
    }

    /**
     * Sets ranking mode.
     *
     * @param $ranker
     * @param  string $rankexpr
     * @return SphinxClient
     */
    public function setRankingMode($ranker, $rankexpr = "")
    {
        assert($ranker === 0 || $ranker >= 1 && $ranker < Ranker::TOTAL);
        assert(is_string($rankexpr));
        $this->_ranker = $ranker;
        $this->_rankexpr = $rankexpr;

        return $this;
    }

    /**
     * Set matches sorting mode.
     *
     * @param $mode
     * @param  string $sortby
     * @return SphinxClient
     */
    public function setSortMode($mode, $sortby = "")
    {
        settype($mode, 'integer'); //If mode is not integer, defaults to Sorter::RELEVANCE.

        assert(
            $mode == Sorter::RELEVANCE ||
            $mode == Sorter::ATTR_DESC ||
            $mode == Sorter::ATTR_ASC ||
            $mode == Sorter::TIME_SEGMENTS ||
            $mode == Sorter::EXTENDED ||
            $mode == Sorter::EXPR);

        assert(is_string($sortby));
        assert($mode == Sorter::RELEVANCE || strlen($sortby) > 0);

        $this->_sort = $mode;
        $this->sortBy = $sortby;

        return $this;
    }

    /**
     * DEPRECATED; Throws exception. Use SetFieldWeights() instead.
     *
     * @param  array $weights
     * @throws \Exception
     */
    public function setWeights(array $weights)
    {
        unset($weights);
        throw new \Exception("setWeights method is deprecated. Use SetFieldWeights() instead.");

    }

    /**
     * Bind per-field weights by name.
     *
     * @param $weights
     * @return SphinxClient
     */
    public function setFieldWeights(array $weights)
    {
        assert(is_array($weights));
        foreach ($weights as $name => $weight) {
            assert(is_string($name));
            assert(is_int($weight));
        }
        $this->_fieldweights = $weights;

        return $this;
    }

    /**
     * Bind per-index weights by name.
     *
     * @param  array $weights
     * @return SphinxClient
     */
    public function setIndexWeights(array $weights)
    {
        assert(is_array($weights));
        foreach ($weights as $index => $weight) {
            assert(is_string($index));
            assert(is_int($weight));
        }
        $this->indexWeights = $weights;

        return $this;
    }

    /**
     * Set IDs range to match. Only match records if document ID is between $min and $max (inclusive)
     *
     * @param $min
     * @param $max
     * @return SphinxClient
     */
    public function setIDRange($min, $max)
    {
        assert(is_numeric($min));
        assert(is_numeric($max));
        assert($min <= $max);
        $this->_min_id = $min;
        $this->_max_id = $max;

        return $this;
    }

    /**
     * Set values for a set filter. Only matches records where $attribute value is in given set.
     *
     * @param $attribute
     * @param  array $values
     * @param  bool $exclude
     * @return SphinxClient
     */
    public function setFilter($attribute, array $values, $exclude = false)
    {
        assert(is_string($attribute));
        assert(is_array($values));
        assert(count($values));

        $exclude = $this->convertToBoolean($exclude);

        if (is_array($values) && count($values)) {
            foreach ($values as $value)
                assert(is_numeric($value));

            $this->_filters[] = array(
                "type" => Filter::VALUES,
                "attr" => $attribute,
                "exclude" => $exclude,
                "values" => $values
            );
        }

        return $this;
    }

    public function setFilterRange($attribute, $min, $max, $exclude = false)
    {
        assert(is_string($attribute));
        assert(is_numeric($min));
        assert(is_numeric($max));
        assert($min <= $max);

        $exclude = $this->convertToBoolean($exclude);

        $this->_filters[] = array(
            "type" => Filter::RANGE,
            "attr" => $attribute,
            "exclude" => $exclude,
            "min" => $min,
            "max" => $max
        );

        return $this;
    }

    /**
     * Sets float range filter. Only matches records if $attribute value is between $min and $max (inclusive).
     *
     * @param $attribute
     * @param $min
     * @param $max
     * @param  bool $exclude
     * @return SphinxClient
     */
    public function setFilterFloatRange($attribute, $min, $max, $exclude = false)
    {
        assert(is_string($attribute));
        assert(is_float($min));
        assert(is_float($max));
        assert($min <= $max);

        $exclude = $this->convertToBoolean($exclude);

        $this->_filters[] = array(
            "type" => Filter::FLOATRANGE,
            "attr" => $attribute,
            "exclude" => $exclude,
            "min" => $min,
            "max" => $max
        );

        return $this;
    }

    /**
     * Set ups anchor point for geosphere distance calculations required to use @geodist in filters and sorting latitude and longitude must be in radians.
     *
     * @param $attrlat
     * @param $attrlong
     * @param $lat
     * @param $long
     * @return SphinxClient
     */
    public function setGeoAnchor($attrlat, $attrlong, $lat, $long)
    {
        assert(is_string($attrlat));
        assert(is_string($attrlong));
        assert(is_float($lat));
        assert(is_float($long));

        $this->_anchor = array("attrlat" => $attrlat, "attrlong" => $attrlong, "lat" => $lat, "long" => $long);

        return $this;
    }

    /**
     * Set grouping attribute and function.
     *
     * @param $attribute
     * @param $func
     * @param  string $groupsort
     * @return SphinxClient
     */
    public function setGroupBy($attribute, $func, $groupsort = "@group desc")
    {
        assert(is_string($attribute));
        assert(is_string($groupsort));
        assert($func == GroupBy::DAY
            || $func == GroupBy::WEEK
            || $func == GroupBy::MONTH
            || $func == GroupBy::YEAR
            || $func == GroupBy::ATTR
            || $func == GroupBy::ATTRPAIR);

        $this->groupBy = $attribute;
        $this->groupFunc = $func;
        $this->groupSort = $groupsort;

        return $this;
    }

    /**
     * Sets count-distinct attribute for group-by queries.
     *
     * @param $attribute
     * @return SphinxClient
     */
    public function setGroupDistinct($attribute)
    {
        assert(is_string($attribute));
        $this->groupDistinct = $attribute;

        return $this;
    }

    /// set range filter
    /// only match records if $attribute value is beetwen $min and $max (inclusive)

    /**
     * Sets distributed retries count and delay values.
     *
     * @param $count
     * @param  int $delay
     * @return SphinxClient
     */
    public function setRetries($count, $delay = 0)
    {
        assert(is_int($count) && $count >= 0);
        assert(is_int($delay) && $delay >= 0);
        $this->_retrycount = (int) $count;
        $this->retryDelay = (int) $delay;

        return $this;
    }

    /**
     * Sets the result set format (hash or array; hash by default).
     * PHP specific; needed for group-by-MVA result sets that may contain duplicate IDs
     *
     * @param $arrayResult
     * @return SphinxClient
     */
    public function setArrayResult($arrayResult)
    {
        assert(is_bool($arrayResult));
        $this->arrayResult = (bool) $arrayResult;

        return $this;
    }

    /**
     * Overrides set attribute values. Attributes can be overridden one by one.
     * $values must be a hash that maps document IDs to attribute values.
     *
     * @param $attrname
     * @param $attrtype
     * @param $values
     * @return SphinxClient
     */
    public function setOverride($attrname, $attrtype, $values)
    {
        assert(is_string($attrname));
        assert(in_array($attrtype, array(
            Attribute::INTEGER,
            Attribute::TIMESTAMP,
            Attribute::BOOL,
            Attribute::FLOAT,
            Attribute::BIGINT
        )));
        assert(is_array($values));

        $this->_overrides[$attrname] = array(
            "attr" => $attrname,
            "type" => $attrtype,
            "values" => $values
        );

        return $this;
    }

    /**
     * Sets a select-list (attributes or expressions), SQL-like syntax.
     *
     * @param $select
     * @return SphinxClient
     */
    public function setSelect($select)
    {
        assert(is_string($select));
        $this->_select = $select;

        return $this;
    }

    /**
     * Clears all filters (for multi-queries).
     *
     * @return SphinxClient
     */
    public function resetFilters()
    {
        $this->_filters = array();
        $this->_anchor = array();

        return $this;
    }

    /**
     * Clears groupby settings (for multi-queries)
     *
     * @return SphinxClient
     */
    public function resetGroupBy()
    {
        $this->groupBy = "";
        $this->groupFunc = GroupBy::DAY;
        $this->groupSort = "@group desc";
        $this->groupDistinct = "";

        return $this;
    }

    /**
     * Clears all attribute value overrides (for multi-queries).
     *
     * @return SphinxClient
     */
    public function resetOverrides()
    {
        $this->_overrides = array();

        return $this;
    }

    /**
     * Connects to searchd server, run given search query through given indexes, and returns the search results.
     *
     * @param $query
     * @param  string $index
     * @param  string $comment
     * @return bool
     */
    public function query($query, $index = "*", $comment = "")
    {
        assert(empty($this->_reqs));

        $this->AddQuery($query, $index, $comment);
        $results = $this->RunQueries();
        $this->_reqs = array(); // just in case it failed too early

        if (!is_array($results))
            return false; // probably network error; error message should be already filled

        $this->_error = $results[0]["error"];
        $this->_warning = $results[0]["warning"];
        if ($results[0]["status"] == Status::ERROR)
            return false;
        else
            return $results[0];
    }

    /**
     * Adds a query to a multi-query batch. Returns index into results array from RunQueries() call.
     *
     * @param $query
     * @param  string $index
     * @param  string $comment
     * @return int
     */
    public function addQuery($query, $index = "*", $comment = "")
    {
        // mbstring workaround
        $this->multiByte->Push();

        // build request
        $req = pack("NNNN", $this->offset, $this->_limit, $this->_mode, $this->_ranker);
        if ($this->_ranker == Ranker::EXPR) {
            $req .= pack("N", strlen($this->_rankexpr)) . $this->_rankexpr;
        }

        $req .= pack("N", $this->_sort); // (deprecated) sort mode
        $req .= pack("N", strlen($this->sortBy)) . $this->sortBy;
        $req .= pack("N", strlen($query)) . $query; // query itself
        $req .= pack("N", count($this->_weights)); // weights
        foreach ($this->_weights as $weight)
            $req .= pack("N", (int)$weight);
        $req .= pack("N", strlen($index)) . $index; // indexes
        $req .= pack("N", 1); // id64 range marker
        $req .= $this->packer->sphPackU64($this->_min_id) . $this->packer->sphPackU64($this->_max_id); // id64 range

        // filters
        $req .= pack("N", count($this->_filters));
        foreach ($this->_filters as $filter) {
            $req .= pack("N", strlen($filter["attr"])) . $filter["attr"];
            $req .= pack("N", $filter["type"]);
            switch ($filter["type"]) {
                case Filter::VALUES:
                    $req .= pack("N", count($filter["values"]));
                    foreach ($filter["values"] as $value)
                        $req .= $this->packer->sphPackI64($value);
                    break;

                case Filter::RANGE:
                    $req .= $this->packer->sphPackI64($filter["min"]) . $this->packer->sphPackI64($filter["max"]);
                    break;

                case Filter::FLOATRANGE:
                    $req .= $this->packer->_PackFloat($filter["min"]) . $this->packer->_PackFloat($filter["max"]);
                    break;

                default:
                    assert(0 && "internal error: unhandled filter type");
            }
            $req .= pack("N", $filter["exclude"]);
        }

        // group-by clause, max-matches count, group-sort clause, cutoff count
        $req .= pack("NN", $this->groupFunc, strlen($this->groupBy)) . $this->groupBy;
        $req .= pack("N", $this->_maxmatches);
        $req .= pack("N", strlen($this->groupSort)) . $this->groupSort;
        $req .= pack("NNN", $this->_cutoff, $this->_retrycount, $this->retryDelay);
        $req .= pack("N", strlen($this->groupDistinct)) . $this->groupDistinct;

        // anchor point
        if (empty($this->_anchor)) {
            $req .= pack("N", 0);
        } else {
            $a =& $this->_anchor;
            $req .= pack("N", 1);
            $req .= pack("N", strlen($a["attrlat"])) . $a["attrlat"];
            $req .= pack("N", strlen($a["attrlong"])) . $a["attrlong"];
            $req .= $this->packer->_PackFloat($a["lat"]) . $this->packer->_PackFloat($a["long"]);
        }

        // per-index weights
        $req .= pack("N", count($this->indexWeights));
        foreach ($this->indexWeights as $idx => $weight)
            $req .= pack("N", strlen($idx)) . $idx . pack("N", $weight);

        // max query time
        $req .= pack("N", $this->_maxquerytime);

        // per-field weights
        $req .= pack("N", count($this->_fieldweights));
        foreach ($this->_fieldweights as $field => $weight)
            $req .= pack("N", strlen($field)) . $field . pack("N", $weight);

        // comment
        $req .= pack("N", strlen($comment)) . $comment;

        // attribute overrides
        $req .= pack("N", count($this->_overrides));
        foreach ($this->_overrides as $entry) {
            $req .= pack("N", strlen($entry["attr"])) . $entry["attr"];
            $req .= pack("NN", $entry["type"], count($entry["values"]));
            foreach ($entry["values"] as $id => $val) {
                assert(is_numeric($id));
                assert(is_numeric($val));

                $req .= $this->packer->sphPackU64($id);
                switch ($entry["type"]) {
                    case Attribute::FLOAT:
                        $req .= $this->packer->_PackFloat($val);
                        break;
                    case Attribute::BIGINT:
                        $req .= $this->packer->sphPackI64($val);
                        break;
                    default:
                        $req .= pack("N", $val);
                        break;
                }
            }
        }

        // select-list
        $req .= pack("N", strlen($this->_select)) . $this->_select;

        // mbstring workaround
        $this->multiByte->Pop();

        // store request to requests array
        $this->_reqs[] = $req;

        return count($this->_reqs) - 1;
    }

    /**
     * Connects to searchd, runs queries in batch, and returns an array of result sets.
     *
     * @return array|bool
     */
    public function runQueries()
    {
        if (empty($this->_reqs)) {
            $this->_error = "no queries defined, issue AddQuery() first";

            return false;
        }

        // mbstring workaround
        $this->multiByte->Push();

        if (!($fp = $this->_Connect())) {
            $this->multiByte->Pop();

            return false;
        }

        // send query, get response
        $nreqs = count($this->_reqs);
        $req = join("", $this->_reqs);
        $len = 8 + strlen($req);
        $req = pack("nnNNN", Command::SEARCH, Version::SEARCH, $len, 0, $nreqs) . $req; // add header

        if (!($this->_Send($fp, $req, $len + 8)) ||
            !($response = $this->_GetResponse($fp, Version::SEARCH))
        ) {
            $this->multiByte->Pop();

            return false;
        }

        // query sent ok; we can reset reqs now
        $this->_reqs = array();

        // parse and return response
        return $this->_ParseSearchResponse($response, $nreqs);
    }

    /**
     * Connects to searchd server, and generate excerpts (snippets) of given documents for given query.
     * Returns false on failure, or an array of snippets on success.
     *
     * @param $docs
     * @param $index
     * @param $words
     * @param  array $opts
     * @return array|bool
     */
    public function buildExcerpts($docs, $index, $words, $opts = array())
    {
        assert(is_array($docs));
        assert(is_string($index));
        assert(is_string($words));
        assert(is_array($opts));

        $this->multiByte->Push();

        if (!($fp = $this->_Connect())) {
            $this->multiByte->Pop();

            return false;
        }

        /////////////////
        // fixup options
        /////////////////

        if (!isset($opts["before_match"])) $opts["before_match"] = "<b>";
        if (!isset($opts["after_match"])) $opts["after_match"] = "</b>";
        if (!isset($opts["chunk_separator"])) $opts["chunk_separator"] = " ... ";
        if (!isset($opts["limit"])) $opts["limit"] = 256;
        if (!isset($opts["limit_passages"])) $opts["limit_passages"] = 0;
        if (!isset($opts["limit_words"])) $opts["limit_words"] = 0;
        if (!isset($opts["around"])) $opts["around"] = 5;
        if (!isset($opts["exact_phrase"])) $opts["exact_phrase"] = false;
        if (!isset($opts["single_passage"])) $opts["single_passage"] = false;
        if (!isset($opts["use_boundaries"])) $opts["use_boundaries"] = false;
        if (!isset($opts["weight_order"])) $opts["weight_order"] = false;
        if (!isset($opts["query_mode"])) $opts["query_mode"] = false;
        if (!isset($opts["force_all_words"])) $opts["force_all_words"] = false;
        if (!isset($opts["start_passage_id"])) $opts["start_passage_id"] = 1;
        if (!isset($opts["load_files"])) $opts["load_files"] = false;
        if (!isset($opts["html_strip_mode"])) $opts["html_strip_mode"] = "index";
        if (!isset($opts["allow_empty"])) $opts["allow_empty"] = false;
        if (!isset($opts["passage_boundary"])) $opts["passage_boundary"] = "none";
        if (!isset($opts["emit_zones"])) $opts["emit_zones"] = false;
        if (!isset($opts["load_files_scattered"])) $opts["load_files_scattered"] = false;

        /////////////////
        // build request
        /////////////////

        // v.1.2 req
        $flags = 1; // remove spaces
        if ($opts["exact_phrase"]) $flags |= 2;
        if ($opts["single_passage"]) $flags |= 4;
        if ($opts["use_boundaries"]) $flags |= 8;
        if ($opts["weight_order"]) $flags |= 16;
        if ($opts["query_mode"]) $flags |= 32;
        if ($opts["force_all_words"]) $flags |= 64;
        if ($opts["load_files"]) $flags |= 128;
        if ($opts["allow_empty"]) $flags |= 256;
        if ($opts["emit_zones"]) $flags |= 512;
        if ($opts["load_files_scattered"]) $flags |= 1024;
        $req = pack("NN", 0, $flags); // mode=0, flags=$flags
        $req .= pack("N", strlen($index)) . $index; // req index
        $req .= pack("N", strlen($words)) . $words; // req words

        // options
        $req .= pack("N", strlen($opts["before_match"])) . $opts["before_match"];
        $req .= pack("N", strlen($opts["after_match"])) . $opts["after_match"];
        $req .= pack("N", strlen($opts["chunk_separator"])) . $opts["chunk_separator"];
        $req .= pack("NN", (int)$opts["limit"], (int)$opts["around"]);
        $req .= pack("NNN", (int)$opts["limit_passages"], (int)$opts["limit_words"], (int)$opts["start_passage_id"]); // v.1.2
        $req .= pack("N", strlen($opts["html_strip_mode"])) . $opts["html_strip_mode"];
        $req .= pack("N", strlen($opts["passage_boundary"])) . $opts["passage_boundary"];

        // documents
        $req .= pack("N", count($docs));
        foreach ($docs as $doc) {
            assert(is_string($doc));
            $req .= pack("N", strlen($doc)) . $doc;
        }

        ////////////////////////////
        // send query, get response
        ////////////////////////////

        $len = strlen($req);
        $req = pack("nnN", Command::EXCERPT, Version::EXCERPT, $len) . $req; // add header
        if (!($this->_Send($fp, $req, $len + 8)) ||
            !($response = $this->_GetResponse($fp, Version::EXCERPT))
        ) {
            $this->multiByte->Pop();

            return false;
        }

        //////////////////
        // parse response
        //////////////////

        $pos = 0;
        $res = array();
        $rlen = strlen($response);
        for ($i = 0; $i < count($docs); $i++) {
            list(, $len) = unpack("N*", substr($response, $pos, 4));
            $pos += 4;

            if ($pos + $len > $rlen) {
                $this->_error = "incomplete reply";
                $this->multiByte->Pop();

                return false;
            }
            $res[] = $len ? substr($response, $pos, $len) : "";
            $pos += $len;
        }

        $this->multiByte->Pop();

        return $res;
    }

    /**
     * Connects to searchd server, and generates a keyword list for a given query.
     * Returns false on failure or an array of words on success.
     *
     * @param $query
     * @param $index
     * @param $hits
     * @return array|bool
     */
    public function buildKeywords($query, $index, $hits)
    {
        assert(is_string($query));
        assert(is_string($index));
        assert(is_bool($hits));

        $this->multiByte->Push();

        if (!($fp = $this->_Connect())) {
            $this->multiByte->Pop();

            return false;
        }

        /////////////////
        // build request
        /////////////////

        // v.1.0 req
        $req = pack("N", strlen($query)) . $query; // req query
        $req .= pack("N", strlen($index)) . $index; // req index
        $req .= pack("N", (int)$hits);

        ////////////////////////////
        // send query, get response
        ////////////////////////////

        $len = strlen($req);
        $req = pack("nnN", Command::KEYWORDS, Version::KEYWORDS, $len) . $req; // add header
        if (!($this->_Send($fp, $req, $len + 8)) ||
            !($response = $this->_GetResponse($fp, Version::KEYWORDS))
        ) {
            $this->multiByte->Pop();

            return false;
        }

        //////////////////
        // parse response
        //////////////////

        $pos = 0;
        $res = array();
        $rlen = strlen($response);
        list(, $nwords) = unpack("N*", substr($response, $pos, 4));
        $pos += 4;
        for ($i = 0; $i < $nwords; $i++) {
            list(, $len) = unpack("N*", substr($response, $pos, 4));
            $pos += 4;
            $tokenized = $len ? substr($response, $pos, $len) : "";
            $pos += $len;

            list(, $len) = unpack("N*", substr($response, $pos, 4));
            $pos += 4;
            $normalized = $len ? substr($response, $pos, $len) : "";
            $pos += $len;

            $res[] = array("tokenized" => $tokenized, "normalized" => $normalized);

            if ($hits) {
                list($ndocs, $nhits) = array_values(unpack("N*N*", substr($response, $pos, 8)));
                $pos += 8;
                $res [$i]["docs"] = $ndocs;
                $res [$i]["hits"] = $nhits;
            }

            if ($pos > $rlen) {
                $this->_error = "incomplete reply";
                $this->multiByte->Pop();

                return false;
            }
        }

        $this->multiByte->Pop();

        return $res;
    }

    /**
     * Escapes a string.
     *
     * @param $string
     * @return string
     */
    public function escapeString($string)
    {
        $from = array('\\', '(', ')', '|', '-', '!', '@', '~', '"', '&', '/', '^', '$', '=');
        $to = array('\\\\', '\(', '\)', '\|', '\-', '\!', '\@', '\~', '\"', '\&', '\/', '\^', '\$', '\=');

        return str_replace($from, $to, $string);
    }

    /**
     * Batch update given attributes in given rows in given indexes.
     * Returns the amount of updated documents (0 or more) on success, or -1 on failure.
     *
     * @param $index
     * @param  array $attrs
     * @param  array $values
     * @param  bool $mva
     * @return int
     */
    public function updateAttributes($index, array $attrs, array $values, $mva = false)
    {
        // verify everything
        assert(is_string($index));
        assert(is_bool($mva));

        assert(is_array($attrs));
        foreach ($attrs as $attr)
            assert(is_string($attr));

        assert(is_array($values));
        foreach ($values as $id => $entry) {
            assert(is_numeric($id));
            assert(is_array($entry));
            assert(count($entry) == count($attrs));
            foreach ($entry as $v) {
                if ($mva) {
                    assert(is_array($v));
                    foreach ($v as $vv)
                        assert(is_int($vv));
                } else
                    assert(is_int($v));
            }
        }

        // build request
        $this->multiByte->Push();
        $req = pack("N", strlen($index)) . $index;

        $req .= pack("N", count($attrs));
        foreach ($attrs as $attr) {
            $req .= pack("N", strlen($attr)) . $attr;
            $req .= pack("N", $mva ? 1 : 0);
        }

        $req .= pack("N", count($values));
        foreach ($values as $id => $entry) {
            $req .= $this->packer->sphPackU64($id);
            foreach ($entry as $v) {
                $req .= pack("N", $mva ? count($v) : $v);
                if ($mva)
                    foreach ($v as $vv)
                        $req .= pack("N", $vv);
            }
        }

        // connect, send query, get response
        if (!($fp = $this->_Connect())) {
            $this->multiByte->Pop();

            return -1;
        }

        $len = strlen($req);
        $req = pack("nnN", Command::UPDATE, Version::UPDATE, $len) . $req; // add header
        if (!$this->_Send($fp, $req, $len + 8)) {
            $this->multiByte->Pop();

            return -1;
        }

        if (!($response = $this->_GetResponse($fp, Version::UPDATE))) {
            $this->multiByte->Pop();

            return -1;
        }

        // parse response
        list(, $updated) = unpack("N*", substr($response, 0, 4));
        $this->multiByte->Pop();

        return $updated;
    }

    /**
     * Opens the connection to searchd.
     *
     * @return bool
     */
    public function open()
    {
        if ($this->_socket !== false) {
            $this->_error = 'already connected';

            return false;
        }
        if (!$fp = $this->_Connect())
            return false;

        // command, command version = 0, body length = 4, body = 1
        $req = pack("nnNN", Command::PERSIST, 0, 4, 1);
        if (!$this->_Send($fp, $req, 12))
            return false;

        $this->_socket = $fp;

        return true;
    }

    /**
     * Closes the connection to searchd.
     *
     * @return bool
     */
    public function close()
    {
        if ($this->_socket === false) {
            $this->_error = 'not connected';

            return false;
        }

        fclose($this->_socket);
        $this->_socket = false;

        return true;
    }

    /**
     * Checks the searchd status.
     * @return array|bool
     */
    public function status()
    {
        $this->multiByte->Push();
        if (!($fp = $this->_Connect())) {
            $this->multiByte->Pop();

            return false;
        }

        $req = pack("nnNN", Command::STATUS, Version::STATUS, 4, 1); // len=4, body=1
        if (!($this->_Send($fp, $req, 12)) ||
            !($response = $this->_GetResponse($fp, Version::STATUS))
        ) {
            $this->multiByte->Pop();

            return false;
        }

        substr($response, 4); // just ignore length, error handling, etc
        $p = 0;
        list ($rows, $cols) = array_values(unpack("N*N*", substr($response, $p, 8)));
        $p += 8;

        $res = array();
        for ($i = 0; $i < $rows; $i++)
            for ($j = 0; $j < $cols; $j++) {
                list(, $len) = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $res[$i][] = substr($response, $p, $len);
                $p += $len;
            }

        $this->multiByte->Pop();

        return $res;
    }

    /**
     * @return int
     */
    public function flushAttributes()
    {
        $this->multiByte->Push();
        if (!($fp = $this->_Connect())) {
            $this->multiByte->Pop();

            return -1;
        }

        $req = pack("nnN", Command::FLUSHATTRS, Version::FLUSHATTRS, 0); // len=0
        if (!($this->_Send($fp, $req, 8)) ||
            !($response = $this->_GetResponse($fp, Version::FLUSHATTRS))
        ) {
            $this->multiByte->Pop();

            return -1;
        }

        $tag = -1;
        if (strlen($response) == 4)
            list(, $tag) = unpack("N*", $response);
        else
            $this->_error = "unexpected response length";

        $this->multiByte->Pop();

        return $tag;
    }


    /**
     * @param $handle
     * @param $data
     * @param $length
     * @return bool
     */
    private function _Send($handle, $data, $length)
    {
        if (feof($handle) || fwrite($handle, $data, $length) !== $length) {
            $this->_error = 'connection unexpectedly closed (timed out?)';
            $this->_connerror = true;

            return false;
        }

        return true;
    }

    /**
     * Connect to searchd server
     * @return bool|resource
     */
    private function _Connect()
    {
        if ($this->_socket !== false) {
            // we are in persistent connection mode, so we have a socket
            // however, need to check whether it's still alive
            if (!@feof($this->_socket))
                return $this->_socket;

            // force reopen
            $this->_socket = false;
        }

        $errno = 0;
        $errstr = "";
        $this->_connerror = false;

        if ($this->_path) {
            $host = $this->_path;
            $port = 0;
        } else {
            $host = $this->host;
            $port = $this->port;
        }

        if ($this->_timeout <= 0)
            $fp = @fsockopen($host, $port, $errno, $errstr);
        else
            $fp = @fsockopen($host, $port, $errno, $errstr, $this->_timeout);

        if (!$fp) {
            if ($this->_path)
                $location = $this->_path;
            else
                $location = "{$this->host}:{$this->port}";

            $errstr = trim($errstr);
            $this->_error = "connection to $location failed (errno=$errno, msg=$errstr)";
            $this->_connerror = true;

            return false;
        }

        // send my version
        // this is a subtle part. we must do it before (!) reading back from searchd.
        // because otherwise under some conditions (reported on FreeBSD for instance)
        // TCP stack could throttle write-write-read pattern because of Nagle.
        if (!$this->_Send($fp, pack("N", 1), 4)) {
            fclose($fp);
            $this->_error = "failed to send client protocol version";

            return false;
        }

        // check version
        list(, $v) = unpack("N*", fread($fp, 4));
        $v = (int)$v;
        if ($v < 1) {
            fclose($fp);
            $this->_error = "expected searchd protocol version 1+, got version '$v'";

            return false;
        }

        return $fp;
    }

    /**
     * Get and check response packet from searchd server.
     *
     * @param $fp
     * @param $client_ver
     * @return bool|string
     */
    private function _GetResponse($fp, $client_ver)
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
        if ($this->_socket === false)
            fclose($fp);

        // check response
        $read = strlen($response);
        if (!$response || $read != $len) {
            $this->_error = $len
                ? "failed to read searchd response (status=$status, ver=$ver, len=$len, read=$read)"
                : "received zero-sized searchd response";

            return false;
        }

        // check status
        if ($status == Status::WARNING) {
            list($temp, $wlen) = unpack("N*", substr($response, 0, 4));
            unset($temp);
            $this->_warning = substr($response, 4, $wlen);

            return substr($response, 4 + $wlen);
        }
        if ($status == Status::ERROR) {
            $this->_error = "searchd error: " . substr($response, 4);

            return false;
        }
        if ($status == Status::RETRY) {
            $this->_error = "temporary searchd error: " . substr($response, 4);

            return false;
        }
        if ($status != Status::OK) {
            $this->_error = "unknown status code '$status'";

            return false;
        }

        // check version
        if ($ver < $client_ver) {
            $this->_warning = sprintf("searchd command v.%d.%d older than client's v.%d.%d, some options might not work",
                $ver >> 8, $ver & 0xff, $client_ver >> 8, $client_ver & 0xff);
        }

        return $response;
    }


    /**
     * Helper function that parses and returns search query (or queries) response
     * @param $response
     * @param $nreqs
     * @return array
     */
    private function _ParseSearchResponse($response, $nreqs)
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

                if ($this->arrayResult)
                    $result["matches"][$idx]["attrs"] = $attrvals;
                else
                    $result["matches"][$doc]["attrs"] = $attrvals;
            }

            list ($total, $total_found, $msecs, $words) =
                array_values(unpack("N*N*N*N*", substr($response, $p, 16)));
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
                $result["words"][$word] = array(
                    "docs" => sprintf("%u", $docs),
                    "hits" => sprintf("%u", $hits));
            }
        }

        $this->multiByte->Pop();

        return $results;
    }

    /**
     * @param $value
     * @return int|string
     */
    private function sphFixUint($value)
    {
        if (PHP_INT_SIZE >= 8) {
            // x64 route, workaround broken unpack() in 5.2.2+
            if ($value < 0) $value += (1 << 32);
            return $value;
        } else {
            // x32 route, workaround php signed/unsigned brain damage
            return sprintf("%u", $value);
        }
    }

    /**
     * @author: Nil Portugués Calderó
     * Converts values to its boolean representation.
     *
     * @param $exclude
     * @return bool
     */
    private function convertToBoolean($exclude)
    {
        if (is_numeric($exclude) && ($exclude == 0 || $exclude == 1)) {
            settype($exclude, 'boolean');
        } elseif (
            $exclude === true
            || $exclude === false
            || strtolower(trim($exclude)) === 'true'
            || strtolower(trim($exclude)) === 'false'
        ) {
            settype($exclude, 'boolean');
        } else {
            $exclude = false;
        }

        return $exclude;
    }
}
