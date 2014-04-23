<?php
/**
 * @author: Andrew Aksyonoff
 * @author: Nil Portugués Calderó
 *
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

/**
 * Known searchd commands.
 */
define("SEARCHD_COMMAND_SEARCH", 0);
define("SEARCHD_COMMAND_EXCERPT", 1);
define("SEARCHD_COMMAND_UPDATE", 2);
define("SEARCHD_COMMAND_KEYWORDS", 3);
define("SEARCHD_COMMAND_PERSIST", 4);
define("SEARCHD_COMMAND_STATUS", 5);
define("SEARCHD_COMMAND_FLUSHATTRS", 7);

/**
 * Current client-side command implementation versions.
 */
define("VER_COMMAND_SEARCH", 0x119);
define("VER_COMMAND_EXCERPT", 0x104);
define("VER_COMMAND_UPDATE", 0x102);
define("VER_COMMAND_KEYWORDS", 0x100);
define("VER_COMMAND_STATUS", 0x100);
define("VER_COMMAND_QUERY", 0x100);
define("VER_COMMAND_FLUSHATTRS", 0x100);

/**
 * Known searchd status codes.
 */
define("SEARCHD_OK", 0);
define("SEARCHD_ERROR", 1);
define("SEARCHD_RETRY", 2);
define("SEARCHD_WARNING", 3);

/**
 * Known match modes.
 */
define("SPH_MATCH_ALL", 0);
define("SPH_MATCH_ANY", 1);
define("SPH_MATCH_PHRASE", 2);
define("SPH_MATCH_BOOLEAN", 3);
define("SPH_MATCH_EXTENDED", 4);
define("SPH_MATCH_FULLSCAN", 5);

// extended engine V2 (TEMPORARY, WILL BE REMOVED)
define("SPH_MATCH_EXTENDED2", 6);

/**
 * Known ranking modes (ext2 only).
 */

// Default mode, phrase proximity major factor and BM25 minor one
define("SPH_RANK_PROXIMITY_BM25", 0);

// Statistical mode, BM25 ranking only (faster but worse quality)
define("SPH_RANK_BM25", 1);

// No ranking, all matches get a weight of 1
define("SPH_RANK_NONE", 2);

// Simple word-count weighting, rank is a weighted sum of per-field keyword occurrence counts
define("SPH_RANK_WORDCOUNT", 3);
define("SPH_RANK_PROXIMITY", 4);
define("SPH_RANK_MATCHANY", 5);
define("SPH_RANK_FIELDMASK", 6);
define("SPH_RANK_SPH04", 7);
define("SPH_RANK_EXPR", 8);
define("SPH_RANK_TOTAL", 9);

/**
 * Known sort modes.
 */
define("SPH_SORT_RELEVANCE", 0);
define("SPH_SORT_ATTR_DESC", 1);
define("SPH_SORT_ATTR_ASC", 2);
define("SPH_SORT_TIME_SEGMENTS", 3);
define("SPH_SORT_EXTENDED", 4);
define("SPH_SORT_EXPR", 5);

/**
 * Known filter types.
 */
define("SPH_FILTER_VALUES", 0);
define("SPH_FILTER_RANGE", 1);
define("SPH_FILTER_FLOATRANGE", 2);

/**
 * Known attribute types.
 */
define("SPH_ATTR_INTEGER", 1);
define("SPH_ATTR_TIMESTAMP", 2);
define("SPH_ATTR_ORDINAL", 3);
define("SPH_ATTR_BOOL", 4);
define("SPH_ATTR_FLOAT", 5);
define("SPH_ATTR_BIGINT", 6);
define("SPH_ATTR_STRING", 7);
define("SPH_ATTR_MULTI", 0x40000001);
define("SPH_ATTR_MULTI64", 0x40000002);

/**
 * Known grouping functions.
 */
define("SPH_GROUPBY_DAY", 0);
define("SPH_GROUPBY_WEEK", 1);
define("SPH_GROUPBY_MONTH", 2);
define("SPH_GROUPBY_YEAR", 3);
define("SPH_GROUPBY_ATTR", 4);
define("SPH_GROUPBY_ATTRPAIR", 5);

/**
 * PHP version of Sphinx searchd client.
 *
 * Class SphinxClient
 * @author Andrew Aksyonoff <andrew.aksyonoff@gmail.com>
 * @author: Nil Portugués Calderó <contact@nilportugues.com>
 * @package NilPortugues\Sphinx
 */
class SphinxClient
{
    /**
     * searchd host (default is "localhost")
     *
     * @var string
     */
    protected $host;

    /**
     * searchd port (default is 9312)
     *
     * @var int
     */
    protected $port;

    /**
     * How many records to seek from result-set start (default is 0)
     *
     * @var int
     */
    protected $offset;

    /**
     * How many records to return from result-set starting at offset (default is 20)
     *
     * @var int
     */
    protected $limit;

    /**
     * Query matching mode (default is SPH_MATCH_ALL)
     *
     * @var int
     */
    protected $mode;

    /**
     * Per-field weights (default is 1 for all fields)
     *
     * @var array
     */
    protected $weights;

    /**
     * Match sorting mode (default is SPH_SORT_RELEVANCE)
     *
     * @var int
     */
    protected $sort;

    /**
     * Attribute to sort by (default is "")
     *
     * @var string
     */
    protected $sortBy;

    /**
     * Min ID to match (default is 0, which means no limit)
     *
     * @var int
     */
    protected $minId;

    /**
     * Max ID to match (default is 0, which means no limit)
     *
     * @var int
     */
    protected $maxId;

    /**
     * Search filters
     *
     * @var array
     */
    protected $filters;

    /**
     * Group-by attribute name
     *
     * @var string
     */
    protected $groupBy;

    /**
     * Group-by function (to pre-process group-by attribute value with)
     *
     * @var int
     */
    protected $groupFunc;

    /**
     * Group-by sorting clause (to sort groups in result set with)
     *
     * @var string
     */
    protected $groupSort;

    /**
     * Group-by count-distinct attribute
     *
     * @var string
     */
    protected $groupDistinct;

    /**
     * Max matches to retrieve
     *
     * @var int
     */
    protected $maxMatches;

    /**
     * Cutoff to stop searching at (default is 0)
     *
     * @var int
     */
    protected $cutOff;

    /**
     * Distributed retries count
     *
     * @var int
     */
    protected $retryCount;

    /**
     * Distributed retries delay
     *
     * @var int
     */
    protected $retryDelay;

    /**
     * Geographical anchor point
     *
     * @var array
     */
    protected $anchor;

    /**
     * Per-index weights
     *
     * @var array
     */
    protected $indexWeights;

    /**
     * Ranking mode (default is SPH_RANK_PROXIMITY_BM25)
     *
     * @var int
     */
    protected $ranker;

    /**
     * Ranking mode expression (for SPH_RANK_EXPR)
     *
     * @var string
     */
    protected $rankExpr;

    /**
     * Max query time, milliseconds (default is 0, do not limit)
     *
     * @var int
     */
    protected $maxQueryTime;

    /**
     * Per-field-name weights
     *
     * @var array
     */
    protected $fieldWeights;

    /**
     * Per-query attribute values overrides
     *
     * @var array
     */
    protected $overrides;

    /**
     * Select-list (attributes or expressions, with optional aliases)
     *
     * @var string
     */
    protected $select;

    /**
     * Last error message
     *
     * @var string
     */
    protected $error;

    /**
     * Last warning message
     *
     * @var string
     */
    protected $warning;

    /**
     * Connection error vs remote error flag
     *
     * @var bool
     */
    protected $connectionError;

    /**
     * Requests array for multi-query
     *
     * @var array
     */
    protected $requests;

    /**
     * Stored mbstring encoding
     *
     * @var string
     */
    protected $mbStringEncoding;

    /**
     * Whether $result["matches"] should be a hash or an array
     *
     * @var bool
     */
    protected $arrayResult;

    /**
     * Connect timeout
     *
     * @var int
     */
    protected $timeout;

    /**
     * @var bool
     */
    protected $path;

    /**
     * @var bool
     */
    protected $socket;

    /**
     * Creates a new client object and fill defaults
     */
    public function __construct()
    {
        // Per-client-object settings
        $this->host = "localhost";
        $this->port = 9312;
        $this->path = false;
        $this->socket = false;

        // Per-query settings
        $this->offset = 0;
        $this->limit = 20;
        $this->mode = SPH_MATCH_ALL;
        $this->weights = array();
        $this->sort = SPH_SORT_RELEVANCE;
        $this->sortBy = "";
        $this->minId = 0;
        $this->maxId = 0;
        $this->filters = array();
        $this->groupBy = "";
        $this->groupFunc = SPH_GROUPBY_DAY;
        $this->groupSort = "@group desc";
        $this->groupDistinct = "";
        $this->maxMatches = 1000;
        $this->cutOff = 0;
        $this->retryCount = 0;
        $this->retryDelay = 0;
        $this->anchor = array();
        $this->indexWeights = array();
        $this->ranker = SPH_RANK_PROXIMITY_BM25;
        $this->rankExpr = "";
        $this->maxQueryTime = 0;
        $this->fieldWeights = array();
        $this->overrides = array();
        $this->select = "*";

        // Per-reply fields (for single-query case)
        $this->error = "";
        $this->warning = "";
        $this->connectionError = false;

        // Requests storage (for multi-query case)
        $this->requests = array();
        $this->mbStringEncoding = "";
        $this->arrayResult = false;
        $this->timeout = 0;
    }

    /**
     * Closes Sphinx socket.
     */
    public function __destruct()
    {
        if ($this->socket !== false) {
            fclose($this->socket);
        }
    }

    /**
     * Gets last error message.
     *
     * @return string
     */
    public function getLastError()
    {
        return $this->error;
    }

    /**
     * Gets last warning message.
     *
     * @return string
     */
    public function getLastWarning()
    {
        return $this->warning;
    }

    /**
     * Get last error flag. It's thought to be used to inform of network
     * connection errors from searchd errors or broken responses.
     *
     * @return boolean
     */
    public function isConnectError()
    {
        return $this->connectionError;
    }

    /**
     * Sets the searchd host name and port.
     *
     * @param $host
     * @param  int          $port
     * @return SphinxClient
     */
    public function setServer($host, $port = 0)
    {
        assert(is_string($host));
        if ($host[0] == '/') {
            $this->path = 'unix://' . $host;
        }

        if (substr($host, 0, 7) == "unix://") {
            $this->path = $host;
        }

        $this->host = $host;
        if (is_int($port)) {
            if ($port) {
                $this->port = $port;
            }
        }
        $this->path = '';

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
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Sets an offset and count into result set, and optionally set max-matches and cutoff limits.
     *
     * @param $offset
     * @param $limit
     * @param  int          $max
     * @param  int          $cutoff
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
        $this->limit = $limit;

        if ($max > 0) {
            $this->maxMatches = $max;
        }

        if ($cutoff > 0) {
            $this->cutOff = $cutoff;
        }

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
        $this->maxQueryTime = $max;

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
        assert(
            $mode == SPH_MATCH_ALL
            || $mode == SPH_MATCH_ANY
            || $mode == SPH_MATCH_PHRASE
            || $mode == SPH_MATCH_BOOLEAN
            || $mode == SPH_MATCH_EXTENDED
            || $mode == SPH_MATCH_FULLSCAN
            || $mode == SPH_MATCH_EXTENDED2
        );
        $this->mode = $mode;

        return $this;
    }

    /**
     * Sets ranking mode.
     *
     * @param $ranker
     * @param  string       $rankExpr
     * @return SphinxClient
     */
    public function setRankingMode($ranker, $rankExpr = "")
    {
        assert($ranker === 0 || $ranker >= 1 && $ranker < SPH_RANK_TOTAL);
        assert(is_string($rankExpr));
        $this->ranker = $ranker;
        $this->rankExpr = $rankExpr;

        return $this;
    }

    /**
     * Set matches sorting mode.
     *
     * @param $mode
     * @param  string       $sortBy
     * @return SphinxClient
     */
    public function setSortMode($mode, $sortBy = "")
    {
        settype($mode, 'integer'); //If mode is not integer, defaults to SPH_SORT_RELEVANCE.

        assert(
            $mode == SPH_SORT_RELEVANCE ||
            $mode == SPH_SORT_ATTR_DESC ||
            $mode == SPH_SORT_ATTR_ASC ||
            $mode == SPH_SORT_TIME_SEGMENTS ||
            $mode == SPH_SORT_EXTENDED ||
            $mode == SPH_SORT_EXPR
        );

        assert(is_string($sortBy));
        assert($mode == SPH_SORT_RELEVANCE || strlen($sortBy) > 0);

        $this->sort = $mode;
        $this->sortBy = $sortBy;

        return $this;
    }

    /**
     * DEPRECATED; Throws exception. Use SetFieldWeights() instead.
     *
     * @param  array      $weights
     * @throws \Exception
     */
    public function setWeights(array $weights)
    {
        unset($weights);
        throw new \Exception("setWeights method is deprecated. Use setFieldWeights() instead.");

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
        $this->fieldWeights = $weights;

        return $this;
    }

    /**
     * Bind per-index weights by name.
     *
     * @param  array        $weights
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
        $this->minId = $min;
        $this->maxId = $max;

        return $this;
    }

    /**
     * Set values for a set filter. Only matches records where $attribute value is in given set.
     *
     * @param $attribute
     * @param  array        $values
     * @param  bool         $exclude
     * @return SphinxClient
     */
    public function setFilter($attribute, array $values, $exclude = false)
    {
        assert(is_string($attribute));
        assert(is_array($values));
        assert(count($values));

        $exclude = $this->convertToBoolean($exclude);

        if (is_array($values) && count($values)) {
            foreach ($values as $value) {
                assert(is_numeric($value));
            }

            $this->filters[] = array(
                "type" => SPH_FILTER_VALUES,
                "attr" => $attribute,
                "exclude" => $exclude,
                "values" => $values
            );
        }

        return $this;
    }

    /**
     * @author: Nil Portugués Calderó <contact@nilportugues.com>
     * Converts values to its boolean representation.
     *
     * @param $exclude
     * @return bool
     */
    protected function convertToBoolean($exclude)
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

    /**
     * @param $attribute
     * @param $min
     * @param $max
     * @param  bool  $exclude
     * @return $this
     */
    public function setFilterRange($attribute, $min, $max, $exclude = false)
    {
        assert(is_string($attribute));
        assert(is_numeric($min));
        assert(is_numeric($max));
        assert($min <= $max);

        $exclude = $this->convertToBoolean($exclude);

        $this->filters[] = array(
            "type" => SPH_FILTER_RANGE,
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
     * @param  bool         $exclude
     * @return SphinxClient
     */
    public function setFilterFloatRange($attribute, $min, $max, $exclude = false)
    {
        assert(is_string($attribute));
        assert(is_float($min));
        assert(is_float($max));
        assert($min <= $max);

        $exclude = $this->convertToBoolean($exclude);

        $this->filters[] = array(
            "type" => SPH_FILTER_FLOATRANGE,
            "attr" => $attribute,
            "exclude" => $exclude,
            "min" => $min,
            "max" => $max
        );

        return $this;
    }

    /**
     * Set ups anchor point for geoSphere distance calculations required to
     * use @geodist in filters and sorting latitude and longitude must be in radians.
     *
     * @param $attrLat
     * @param $attrLong
     * @param $lat
     * @param $long
     * @return SphinxClient
     */
    public function setGeoAnchor($attrLat, $attrLong, $lat, $long)
    {
        assert(is_string($attrLat));
        assert(is_string($attrLong));
        assert(is_float($lat));
        assert(is_float($long));

        $this->anchor = array(
            "attrlat" => $attrLat,
            "attrlong" => $attrLong,
            "lat" => $lat,
            "long" => $long
        );

        return $this;
    }

    /**
     * Set grouping attribute and function.
     *
     * @param $attribute
     * @param $func
     * @param  string       $groupSort
     * @return SphinxClient
     */
    public function setGroupBy($attribute, $func, $groupSort = "@group desc")
    {
        assert(is_string($attribute));
        assert(is_string($groupSort));

        assert(
            $func == SPH_GROUPBY_DAY
            || $func == SPH_GROUPBY_WEEK
            || $func == SPH_GROUPBY_MONTH
            || $func == SPH_GROUPBY_YEAR
            || $func == SPH_GROUPBY_ATTR
            || $func == SPH_GROUPBY_ATTRPAIR
        );

        $this->groupBy = $attribute;
        $this->groupFunc = $func;
        $this->groupSort = $groupSort;

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

    /**
     * Sets distributed retries count and delay values.
     *
     * @param $count
     * @param  int          $delay
     * @return SphinxClient
     */
    public function setRetries($count, $delay = 0)
    {
        assert(is_int($count) && $count >= 0);
        assert(is_int($delay) && $delay >= 0);

        $this->retryCount = $count;
        $this->retryDelay = $delay;

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
        $this->arrayResult = $arrayResult;

        return $this;
    }

    /**
     * Overrides set attribute values. Attributes can be overridden one by one.
     * $values must be a hash that maps document IDs to attribute values.
     *
     * @param $attrName
     * @param $attrType
     * @param $values
     * @return SphinxClient
     */
    public function setOverride($attrName, $attrType, $values)
    {
        assert(is_string($attrName));
        assert(in_array($attrType, array(
            SPH_ATTR_INTEGER,
            SPH_ATTR_TIMESTAMP,
            SPH_ATTR_BOOL,
            SPH_ATTR_FLOAT,
            SPH_ATTR_BIGINT
        )));

        assert(is_array($values));

        $this->overrides[$attrName] = array(
            "attr" => $attrName,
            "type" => $attrType,
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
        $this->select = $select;

        return $this;
    }

    /**
     * Clears all filters (for multi-queries).
     *
     * @return SphinxClient
     */
    public function resetFilters()
    {
        $this->filters = array();
        $this->anchor = array();

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
        $this->groupFunc = SPH_GROUPBY_DAY;
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
        $this->overrides = array();

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
        assert(empty($this->requests));

        $this->addQuery($query, $index, $comment);
        $results = $this->runQueries();
        $this->requests = array(); // just in case it failed too early

        if (!is_array($results)) {
            return false; // probably network error; error message should be already filled
        }

        $this->error = $results[0]["error"];
        $this->warning = $results[0]["warning"];
        if ($results[0]["status"] == SEARCHD_ERROR) {
            return false;
        } else {
            return $results[0];
        }
    }

    /**
     * @author: Nil Portugués <contact@nilportugues.com>
     *
     * Builds the request for the addQuery method.
     *
     * @param $query
     * @param $index
     *
     * @return string
     */
    protected function buildAddQueryRequest($query, $index)
    {
        $request= '';

        if ($this->ranker == SPH_RANK_EXPR) {
            $request.= pack("N", strlen($this->rankExpr)) . $this->rankExpr;
        }

        $request.= pack("N", $this->sort); // (deprecated) sort mode
        $request.= pack("N", strlen($this->sortBy)) . $this->sortBy;
        $request.= pack("N", strlen($query)) . $query; // query itself
        $request.= pack("N", count($this->weights)); // weights

        foreach ($this->weights as $weight) {
            $request.= pack("N", (int) $weight);
        }

        $request.= pack("N", strlen($index)) . $index; // indexes
        $request.= pack("N", 1); // id64 range marker
        $request.= $this->sphPackU64($this->minId) . $this->sphPackU64($this->maxId);

        return $request;
    }


    /**
     * @author: Nil Portugués <contact@nilportugues.com>
     *
     * Builds the request filter for the addQuery method.
     *
     * @return string
     */
    protected function buildAddQueryFilterRequest()
    {
        $request= '';
        $request.= pack("N", count($this->filters));
        foreach ($this->filters as $filter) {

            $request.= pack("N", strlen($filter["attr"])) . $filter["attr"];
            $request.= pack("N", $filter["type"]);

            switch ($filter["type"]) {
                case SPH_FILTER_VALUES:
                    $request.= pack("N", count($filter["values"]));
                    foreach ($filter["values"] as $value) {
                        $request.= $this->sphPackI64($value);
                    }
                    break;

                case SPH_FILTER_RANGE:
                    $request.= $this->sphPackI64($filter["min"]) . $this->sphPackI64($filter["max"]);
                    break;

                case SPH_FILTER_FLOATRANGE:
                    $request.= $this->packFloat($filter["min"]) . $this->packFloat($filter["max"]);
                    break;

                default:
                    assert(0 && "internal error: unhandled filter type");
            }
            $request.= pack("N", $filter["exclude"]);
        }

        return $request;
    }


    /**
     * Adds a query to a multi-query batch. Returns index into results array from runQueries() call.
     *
     * @param $query
     * @param  string $index
     * @param  string $comment
     * @return int
     */
    public function addQuery($query, $index = "*", $comment = "")
    {
        // mbstring workaround
        $this->mbPush();

        $request = pack("NNNN", $this->offset, $this->limit, $this->mode, $this->ranker);
        $request.= $this->buildAddQueryRequest($query, $index);
        $request.= $this->buildAddQueryFilterRequest();

        // group-by clause
        $request.= pack("NN", $this->groupFunc, strlen($this->groupBy)) . $this->groupBy;

        //max-matches count
        $request.= pack("N", $this->maxMatches);

        //group-sort clause
        $request.= pack("N", strlen($this->groupSort)) . $this->groupSort;

        //cutoff count
        $request.= pack("NNN", $this->cutOff, $this->retryCount, $this->retryDelay);

        //group-distinct clause
        $request.= pack("N", strlen($this->groupDistinct)) . $this->groupDistinct;

        // anchor point
        if (empty($this->anchor)) {
            $request.= pack("N", 0);
        } else {
            $a =& $this->anchor;
            $request.= pack("N", 1);
            $request.= pack("N", strlen($a["attrlat"])) . $a["attrlat"];
            $request.= pack("N", strlen($a["attrlong"])) . $a["attrlong"];
            $request.= $this->packFloat($a["lat"]) . $this->packFloat($a["long"]);
        }

        // per-index weights
        $request.= pack("N", count($this->indexWeights));
        foreach ($this->indexWeights as $idx => $weight) {
            $request.= pack("N", strlen($idx)) . $idx . pack("N", $weight);
        }

        // max query time
        $request.= pack("N", $this->maxQueryTime);

        // per-field weights
        $request.= pack("N", count($this->fieldWeights));
        foreach ($this->fieldWeights as $field => $weight) {
            $request.= pack("N", strlen($field)) . $field . pack("N", $weight);
        }

        // comment
        $request.= pack("N", strlen($comment)) . $comment;

        // attribute overrides
        $request.= pack("N", count($this->overrides));
        foreach ($this->overrides as $entry) {

            $request.= pack("N", strlen($entry["attr"])) . $entry["attr"];
            $request.= pack("NN", $entry["type"], count($entry["values"]));

            foreach ($entry["values"] as $id => $val) {
                assert(is_numeric($id));
                assert(is_numeric($val));

                $request.= $this->sphPackU64($id);
                switch ($entry["type"]) {
                    case SPH_ATTR_FLOAT:
                        $request.= $this->packFloat($val);
                        break;
                    case SPH_ATTR_BIGINT:
                        $request.= $this->sphPackI64($val);
                        break;
                    default:
                        $request.= pack("N", $val);
                        break;
                }
            }
        }

        // select-list
        $request.= pack("N", strlen($this->select)) . $this->select;

        // mbstring workaround
        $this->mbPop();

        // store request to requests array
        $this->requests[] = $request;

        return count($this->requests) - 1;
    }

    /**
     * Enter mbstring workaround mode
     */
    protected function mbPush()
    {
        $this->mbStringEncoding = "";

        if (ini_get("mbstring.func_overload") & 2) {
            $this->mbStringEncoding = mb_internal_encoding();
            mb_internal_encoding("latin1");
        }
    }

    /**
     * Packs 64-bit unsigned.
     *
     * @param $v
     * @return string
     */
    protected function sphPackU64($v)
    {
        assert(is_numeric($v));

        // x64
        if (PHP_INT_SIZE >= 8) {
            assert($v >= 0);

            // x64, int
            if (is_int($v)) {
                return pack("NN", $v >> 32, $v & 0xFFFFFFFF);
            }

            // x64, bcmath
            if (function_exists("bcmul")) {
                $h = bcdiv($v, 4294967296, 0);
                $l = bcmod($v, 4294967296);

                return pack("NN", $h, $l);
            }

            // x64, no-bcmath
            $p = max(0, strlen($v) - 13);
            $lo = (int) substr($v, $p);
            $hi = (int) substr($v, 0, $p);

            $m = $lo + $hi * 1316134912;
            $l = $m % 4294967296;
            $h = $hi * 2328 + (int) ($m / 4294967296);

            return pack("NN", $h, $l);
        }

        // x32, int
        if (is_int($v)) {
            return pack("NN", 0, $v);
        }

        // x32, bcmath
        if (function_exists("bcmul")) {
            $h = bcdiv($v, "4294967296", 0);
            $l = bcmod($v, "4294967296");

            return pack("NN", (float) $h, (float) $l); // conversion to float is intentional; int would lose 31st bit
        }

        // x32, no-bcmath
        $p = max(0, strlen($v) - 13);
        $lo = (float) substr($v, $p);
        $hi = (float) substr($v, 0, $p);

        $m = $lo + $hi * 1316134912.0;
        $q = floor($m / 4294967296.0);
        $l = $m - ($q * 4294967296.0);
        $h = $hi * 2328.0 + $q;

        return pack("NN", $h, $l);
    }

    /**
     * Pack 64-bit signed.
     *
     * @param $v
     * @return string
     */
    protected function sphPackI64($v)
    {
        assert(is_numeric($v));

        // x64
        if (PHP_INT_SIZE >= 8) {
            $v = (int) $v;

            return pack("NN", $v >> 32, $v & 0xFFFFFFFF);
        }

        // x32, int
        if (is_int($v)) {
            return pack("NN", $v < 0 ? -1 : 0, $v);
        }

        // x32, bcmath
        if (function_exists("bcmul")) {

            if (bccomp($v, 0) == -1) {
                $v = bcadd("18446744073709551616", $v);
            }

            $h = bcdiv($v, "4294967296", 0);
            $l = bcmod($v, "4294967296");

            return pack("NN", (float) $h, (float) $l); // conversion to float is intentional; int would lose 31st bit
        }

        // x32, no-bcmath
        $p = max(0, strlen($v) - 13);
        $lo = abs((float) substr($v, $p));
        $hi = abs((float) substr($v, 0, $p));

        $m = $lo + $hi * 1316134912.0; // (10 ^ 13) % (1 << 32) = 1316134912
        $q = floor($m / 4294967296.0);
        $l = $m - ($q * 4294967296.0);
        $h = $hi * 2328.0 + $q; // (10 ^ 13) / (1 << 32) = 2328

        if ($v < 0) {
            if ($l == 0) {
                $h = 4294967296.0 - $h;
            } else {
                $h = 4294967295.0 - $h;
                $l = 4294967296.0 - $l;
            }
        }

        return pack("NN", $h, $l);
    }

    /**
     * Helper function to pack floats in network byte order.
     *
     * @param $f
     * @return string
     */
    protected function packFloat($f)
    {
        $t1 = pack("f", $f); // machine order
        list(, $t2) = unpack("L*", $t1); // int in machine order

        return pack("N", $t2);
    }

    /**
     * Leave mbstring workaround mode
     */
    protected function mbPop()
    {
        if ($this->mbStringEncoding) {
            mb_internal_encoding($this->mbStringEncoding);
        }
    }

    /**
     * Connects to searchd, runs queries in batch, and returns an array of result sets.
     *
     * @return array|bool
     */
    public function runQueries()
    {
        if (empty($this->requests)) {
            $this->error = "no queries defined, issue addQuery() first";

            return false;
        }

        // mbstring workaround
        $this->mbPush();

        if (!($fp = $this->connect())) {
            $this->mbPop();

            return false;
        }

        // send query, get response
        $totalRequests = count($this->requests);
        $request= join("", $this->requests);
        $len = 8 + strlen($request);
        $request= pack("nnNNN", SEARCHD_COMMAND_SEARCH, VER_COMMAND_SEARCH, $len, 0, $totalRequests) . $request; // add header

        if (!($this->send($fp, $request, $len + 8)) ||
            !($response = $this->getResponse($fp, VER_COMMAND_SEARCH))
        ) {
            $this->mbPop();

            return false;
        }

        // query sent ok; we can reset requests now
        $this->requests = array();

        // parse and return response
        return $this->parseSearchResponse($response, $totalRequests);
    }

    /**
     * Connect to searchd server
     * @return bool|resource
     */
    protected function connect()
    {
        if ($this->socket !== false) {

            // we are in persistent connection mode, so we have a socket
            // however, need to check whether it's still alive

            if (!@feof($this->socket)) {
                return $this->socket;
            }

            // force reopen
            $this->socket = false;
        }

        $errorNumber = 0;
        $errorString = "";
        $this->connectionError = false;

        if ($this->path) {
            $host = $this->path;
            $port = 0;
        } else {
            $host = $this->host;
            $port = $this->port;
        }

        if ($this->timeout <= 0) {
            $fp = @fsockopen($host, $port, $errorNumber, $errorString);
        } else {
            $fp = @fsockopen($host, $port, $errorNumber, $errorString, $this->timeout);
        }

        if (!$fp) {
            if ($this->path) {
                $location = $this->path;
            } else {
                $location = "{$this->host}:{$this->port}";
            }

            $errorString = trim($errorString);
            $this->error = "connection to $location failed (errno=$errorNumber, msg=$errorString)";
            $this->connectionError = true;

            return false;
        }

        // send my version
        // this is a subtle part. we must do it before (!) reading back from searchd.
        // because otherwise under some conditions (reported on FreeBSD for instance)
        // TCP stack could throttle write-write-read pattern because of Nagle.
        if (!$this->send($fp, pack("N", 1), 4)) {
            fclose($fp);
            $this->error = "failed to send client protocol version";

            return false;
        }

        // check version
        list(, $v) = unpack("N*", fread($fp, 4));
        $v = (int) $v;

        if ($v < 1) {
            fclose($fp);
            $this->error = "expected searchd protocol version 1+, got version '$v'";

            return false;
        }

        return $fp;
    }

    /**
     * @param $handle
     * @param $data
     * @param $length
     * @return bool
     */
    protected function send($handle, $data, $length)
    {
        if (feof($handle) || fwrite($handle, $data, $length) !== $length) {
            $this->error = 'connection unexpectedly closed (timed out?)';
            $this->connectionError = true;

            return false;
        }

        return true;
    }

    /**
     * Get and check response packet from searchd server.
     *
     * @param $fp
     * @param $clientVersion
     * @return bool|string
     */
    protected function getResponse($fp, $clientVersion)
    {
        $status = '';
        $response = "";
        $len = 0;
        $ver = '';

        $header = fread($fp, 8);
        if (strlen($header) == 8) {
            list($status, $ver, $len) = array_values(unpack("n2a/Nb", $header));
            $left = $len;
            while ($left > 0 && !feof($fp)) {
                $chunk = fread($fp, min(8192, $left));
                if ($chunk) {
                    $response .= $chunk;
                    $left -= strlen($chunk);
                }
            }
        }

        if ($this->socket === false) {
            fclose($fp);
        }

        // check response
        $read = strlen($response);

        if (!$response || $read != $len) {
            $this->error = $len
                ? "failed to read searchd response (status=$status, ver=$ver, len=$len, read=$read)"
                : "received zero-sized searchd response";

            return false;
        }

        // check status
        if ($status == SEARCHD_WARNING) {
            list($temp, $wlen) = unpack("N*", substr($response, 0, 4));
            unset($temp);
            $this->warning = substr($response, 4, $wlen);

            return substr($response, 4 + $wlen);
        }

        if ($status == SEARCHD_ERROR) {
            $this->error = "searchd error: " . substr($response, 4);

            return false;
        }

        if ($status == SEARCHD_RETRY) {
            $this->error = "temporary searchd error: " . substr($response, 4);

            return false;
        }

        if ($status != SEARCHD_OK) {
            $this->error = "unknown status code '$status'";

            return false;
        }

        // check version
        if ($ver < $clientVersion) {
            $this->warning = sprintf(
                "searchd command v.%d.%d older than client's v.%d.%d, some options might not work",
                $ver >> 8,
                $ver & 0xff,
                $clientVersion >> 8,
                $clientVersion & 0xff
            );
        }

        return $response;
    }

    /**
     * Helper function that parses and returns search query (or queries) response
     * @param $response
     * @param $totalRequests
     * @return array
     */
    protected function parseSearchResponse($response, $totalRequests)
    {
        $p = 0; // current position
        $max = strlen($response); // max position for checks, to protect against broken responses

        $results = array();
        for ($ires = 0; $ires < $totalRequests && $p < $max; $ires++) {
            $results[] = array();
            $result =& $results[$ires];

            $result["error"] = "";
            $result["warning"] = "";

            // extract status
            list(, $status) = unpack("N*", substr($response, $p, 4));
            $p += 4;
            $result["status"] = $status;
            if ($status != SEARCHD_OK) {
                list(, $len) = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $message = substr($response, $p, $len);
                $p += $len;

                if ($status == SEARCHD_WARNING) {
                    $result["warning"] = $message;
                } else {
                    $result["error"] = $message;
                    continue;
                }
            }

            // read schema
            $fields = array();
            $attributes = array();

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
                $attributes[$attr] = $type;
            }
            $result["attrs"] = $attributes;

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
                    $doc = $this->sphUnpackU64(substr($response, $p, 8));
                    $p += 8;
                    list(, $weight) = unpack("N*", substr($response, $p, 4));
                    $p += 4;
                } else {
                    list($doc, $weight) = array_values(unpack("N*N*", substr($response, $p, 8)));
                    $p += 8;
                    $doc = $this->sphFixUnsignedInt($doc);
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
                foreach ($attributes as $attr => $type) {
                    // handle 64bit ints
                    if ($type == SPH_ATTR_BIGINT) {
                        $attrvals[$attr] = $this->sphUnpackI64(substr($response, $p, 8));
                        $p += 8;
                        continue;
                    }

                    // handle floats
                    if ($type == SPH_ATTR_FLOAT) {
                        list(, $uval) = unpack("N*", substr($response, $p, 4));
                        $p += 4;
                        list(, $fval) = unpack("f*", pack("L", $uval));
                        $attrvals[$attr] = $fval;
                        continue;
                    }

                    // handle everything else as unsigned ints
                    list(, $val) = unpack("N*", substr($response, $p, 4));
                    $p += 4;
                    if ($type == SPH_ATTR_MULTI) {
                        $attrvals[$attr] = array();
                        $nvalues = $val;
                        while ($nvalues-- > 0 && $p < $max) {
                            list(, $val) = unpack("N*", substr($response, $p, 4));
                            $p += 4;
                            $attrvals[$attr][] = $this->sphFixUnsignedInt($val);
                        }
                    } elseif ($type == SPH_ATTR_MULTI64) {
                        $attrvals[$attr] = array();
                        $nvalues = $val;
                        while ($nvalues > 0 && $p < $max) {
                            $attrvals[$attr][] = $this->sphUnpackI64(substr($response, $p, 8));
                            $p += 8;
                            $nvalues -= 2;
                        }
                    } elseif ($type == SPH_ATTR_STRING) {
                        $attrvals[$attr] = substr($response, $p, $val);
                        $p += $val;
                    } else {
                        $attrvals[$attr] = $this->sphFixUnsignedInt($val);
                    }
                }

                if ($this->arrayResult) {
                    $result["matches"][$idx]["attrs"] = $attrvals;
                } else {
                    $result["matches"][$doc]["attrs"] = $attrvals;
                }
            }

            list($total, $total_found, $msecs, $words) = array_values(unpack("N*N*N*N*", substr($response, $p, 16)));

            $result["total"] = sprintf("%u", $total);
            $result["total_found"] = sprintf("%u", $total_found);
            $result["time"] = sprintf("%.3f", $msecs / 1000);
            $p += 16;

            while ($words-- > 0 && $p < $max) {
                list(, $len) = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $word = substr($response, $p, $len);
                $p += $len;
                list($documents, $hits) = array_values(unpack("N*N*", substr($response, $p, 8)));
                $p += 8;
                $result["words"][$word] = array(
                    "docs" => sprintf("%u", $documents),
                    "hits" => sprintf("%u", $hits)
                );
            }
        }

        $this->mbPop();

        return $results;
    }

    /**
     * Unpacks 64-bit unsigned.
     *
     * @param $v
     * @return int|string
     */
    protected function sphUnpackU64($v)
    {
        list($hi, $lo) = array_values(unpack("N*N*", $v));

        if (PHP_INT_SIZE >= 8) {

            // because php 5.2.2 to 5.2.5 is totally fucked up again
            if ($hi < 0) {
                $hi += (1 << 32);
            }
            if ($lo < 0) {
                $lo += (1 << 32);
            }

            // x64, int
            if ($hi <= 2147483647) {
                return ($hi << 32) + $lo;
            }

            // x64, bcmath
            if (function_exists("bcmul")) {
                return bcadd($lo, bcmul($hi, "4294967296"));
            }

            // x64, no-bcmath
            $C = 100000;
            $h = ((int) ($hi / $C) << 32) + (int) ($lo / $C);
            $l = (($hi % $C) << 32) + ($lo % $C);
            if ($l > $C) {
                $h += (int) ($l / $C);
                $l = $l % $C;
            }

            if ($h == 0) {
                return $l;
            }

            return sprintf("%d%05d", $h, $l);
        }

        // x32, int
        if ($hi == 0) {
            if ($lo > 0) {
                return $lo;
            }

            return sprintf("%u", $lo);
        }

        $hi = sprintf("%u", $hi);
        $lo = sprintf("%u", $lo);

        // x32, bcmath
        if (function_exists("bcmul")) {
            return bcadd($lo, bcmul($hi, "4294967296"));
        }

        // x32, no-bcmath
        $hi = (float) $hi;
        $lo = (float) $lo;

        $q = floor($hi / 10000000.0);
        $r = $hi - $q * 10000000.0;
        $m = $lo + $r * 4967296.0;
        $mq = floor($m / 10000000.0);
        $l = $m - $mq * 10000000.0;
        $h = $q * 4294967296.0 + $r * 429.0 + $mq;

        $h = sprintf("%.0f", $h);
        $l = sprintf("%07.0f", $l);
        if ($h == "0") {
            return sprintf("%.0f", (float) $l);
        }

        return $h . $l;
    }

    /**
     * @param $value
     * @return int|string
     */
    protected function sphFixUnsignedInt($value)
    {
        if (PHP_INT_SIZE >= 8) {
            // x64 route, workaround broken unpack() in 5.2.2+
            if ($value < 0) {
                $value += (1 << 32);
            }

            return $value;
        } else {
            // x32 route, workaround php signed/unsigned brain damage
            return sprintf("%u", $value);
        }
    }

    /**
     * Unpacks 64-bit signed.
     *
     * @param $v
     * @return int|string
     */
    protected function sphUnpackI64($v)
    {
        list($hi, $lo) = array_values(unpack("N*N*", $v));

        // x64
        if (PHP_INT_SIZE >= 8) {

            // because php 5.2.2 to 5.2.5 is totally fucked up again
            if ($hi < 0) {
                $hi += (1 << 32);
            }
            if ($lo < 0) {
                $lo += (1 << 32);
            }

            return ($hi << 32) + $lo;
        }

        // x32, int
        if ($hi == 0) {
            if ($lo > 0) {
                return $lo;
            }

            return sprintf("%u", $lo);
        } elseif ($hi == -1) {
            if ($lo < 0) {
                return $lo;
            }

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
        if (function_exists("bcmul")) {
            return $neg . bcadd(bcadd($lo, bcmul($hi, "4294967296")), $c);
        }

        // x32, no-bcmath
        $hi = (float) $hi;
        $lo = (float) $lo;

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
        if ($h == "0") {
            return $neg . sprintf("%.0f", (float) $l);
        }

        return $neg . $h . $l;
    }

    /**
     * Connects to searchd server, and generate excerpts (snippets) of given documents for given query.
     * Returns false on failure, or an array of snippets on success.
     *
     * @param $documents
     * @param $index
     * @param $words
     * @param  array      $options
     * @return array|bool
     */
    public function buildExcerpts($documents, $index, $words, $options = array())
    {
        assert(is_array($documents));
        assert(is_string($index));
        assert(is_string($words));
        assert(is_array($options));

        $this->mbPush();

        if (!($fp = $this->connect())) {
            $this->mbPop();

            return false;
        }

        // fixup options
        if (!isset($options["before_match"])) {
            $options["before_match"] = "<b>";
        }
        if (!isset($options["after_match"])) {
            $options["after_match"] = "</b>";
        }
        if (!isset($options["chunk_separator"])) {
            $options["chunk_separator"] = " ... ";
        }
        if (!isset($options["limit"])) {
            $options["limit"] = 256;
        }
        if (!isset($options["limit_passages"])) {
            $options["limit_passages"] = 0;
        }
        if (!isset($options["limit_words"])) {
            $options["limit_words"] = 0;
        }
        if (!isset($options["around"])) {
            $options["around"] = 5;
        }
        if (!isset($options["exact_phrase"])) {
            $options["exact_phrase"] = false;
        }
        if (!isset($options["single_passage"])) {
            $options["single_passage"] = false;
        }
        if (!isset($options["use_boundaries"])) {
            $options["use_boundaries"] = false;
        }
        if (!isset($options["weight_order"])) {
            $options["weight_order"] = false;
        }
        if (!isset($options["querymode"])) {
            $options["querymode"] = false;
        }
        if (!isset($options["force_all_words"])) {
            $options["force_all_words"] = false;
        }
        if (!isset($options["start_passage_id"])) {
            $options["start_passage_id"] = 1;
        }
        if (!isset($options["load_files"])) {
            $options["load_files"] = false;
        }
        if (!isset($options["html_stripmode"])) {
            $options["html_stripmode"] = "index";
        }
        if (!isset($options["allow_empty"])) {
            $options["allow_empty"] = false;
        }
        if (!isset($options["passage_boundary"])) {
            $options["passage_boundary"] = "none";
        }
        if (!isset($options["emit_zones"])) {
            $options["emit_zones"] = false;
        }
        if (!isset($options["load_files_scattered"])) {
            $options["load_files_scattered"] = false;
        }

        // build request

        // v.1.2 req
        $flags = 1; // remove spaces
        if ($options["exact_phrase"]) {
            $flags |= 2;
        }
        if ($options["single_passage"]) {
            $flags |= 4;
        }
        if ($options["use_boundaries"]) {
            $flags |= 8;
        }
        if ($options["weight_order"]) {
            $flags |= 16;
        }
        if ($options["querymode"]) {
            $flags |= 32;
        }
        if ($options["force_all_words"]) {
            $flags |= 64;
        }
        if ($options["load_files"]) {
            $flags |= 128;
        }
        if ($options["allow_empty"]) {
            $flags |= 256;
        }
        if ($options["emit_zones"]) {
            $flags |= 512;
        }
        if ($options["load_files_scattered"]) {
            $flags |= 1024;
        }

        $request= pack("NN", 0, $flags); // mode=0, flags=$flags
        $request.= pack("N", strlen($index)) . $index; // req index
        $request.= pack("N", strlen($words)) . $words; // req words

        // options
        $request.= pack("N", strlen($options["before_match"])) . $options["before_match"];
        $request.= pack("N", strlen($options["after_match"])) . $options["after_match"];
        $request.= pack("N", strlen($options["chunk_separator"])) . $options["chunk_separator"];
        $request.= pack(
            "NN",
            (int) $options["limit"],
            (int) $options["around"]
        );

        $request.= pack(
            "NNN",
            (int) $options["limit_passages"],
            (int) $options["limit_words"],
            (int) $options["start_passage_id"]
        ); // v.1.2

        $request.= pack("N", strlen($options["html_stripmode"])) . $options["html_stripmode"];
        $request.= pack("N", strlen($options["passage_boundary"])) . $options["passage_boundary"];

        // documents
        $request.= pack("N", count($documents));
        foreach ($documents as $doc) {
            assert(is_string($doc));
            $request.= pack("N", strlen($doc)) . $doc;
        }

        // send query, get response
        $len = strlen($request);
        $request= pack("nnN", SEARCHD_COMMAND_EXCERPT, VER_COMMAND_EXCERPT, $len) . $request; // add header
        if (!($this->send($fp, $request, $len + 8)) ||
            !($response = $this->getResponse($fp, VER_COMMAND_EXCERPT))
        ) {
            $this->mbPop();

            return false;
        }

        // parse response
        $pos = 0;
        $res = array();
        $rlen = strlen($response);
        for ($i = 0; $i < count($documents); $i++) {
            list(, $len) = unpack("N*", substr($response, $pos, 4));
            $pos += 4;

            if ($pos + $len > $rlen) {
                $this->error = "incomplete reply";
                $this->mbPop();

                return false;
            }
            $res[] = $len ? substr($response, $pos, $len) : "";
            $pos += $len;
        }

        $this->mbPop();

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

        $this->mbPush();

        if (!($fp = $this->connect())) {
            $this->mbPop();

            return false;
        }

        /////////////////
        // build request
        /////////////////

        // v.1.0 req
        $request= pack("N", strlen($query)) . $query; // req query
        $request.= pack("N", strlen($index)) . $index; // req index
        $request.= pack("N", (int) $hits);

        ////////////////////////////
        // send query, get response
        ////////////////////////////

        $len = strlen($request);
        $request= pack("nnN", SEARCHD_COMMAND_KEYWORDS, VER_COMMAND_KEYWORDS, $len) . $request; // add header
        if (!($this->send($fp, $request, $len + 8)) ||
            !($response = $this->getResponse($fp, VER_COMMAND_KEYWORDS))
        ) {
            $this->mbPop();

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
                $this->error = "incomplete reply";
                $this->mbPop();

                return false;
            }
        }

        $this->mbPop();

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
     * @param  array $attributes
     * @param  array $values
     * @param  bool  $mva
     * @return int
     */
    public function updateAttributes($index, array $attributes, array $values, $mva = false)
    {
        // verify everything
        assert(is_string($index));
        assert(is_bool($mva));

        assert(is_array($attributes));
        foreach ($attributes as $attr) {
            assert(is_string($attr));
        }

        assert(is_array($values));
        foreach ($values as $id => $entry) {
            assert(is_numeric($id));
            assert(is_array($entry));
            assert(count($entry) == count($attributes));
            foreach ($entry as $v) {
                if ($mva) {
                    assert(is_array($v));
                    foreach ($v as $vv) {
                        assert(is_int($vv));
                    }
                } else {
                    assert(is_int($v));
                }
            }
        }

        // build request
        $this->mbPush();
        $request= pack("N", strlen($index)) . $index;

        $request.= pack("N", count($attributes));
        foreach ($attributes as $attr) {
            $request.= pack("N", strlen($attr)) . $attr;
            $request.= pack("N", $mva ? 1 : 0);
        }

        $request.= pack("N", count($values));
        foreach ($values as $id => $entry) {
            $request.= $this->sphPackU64($id);
            foreach ($entry as $v) {
                $request.= pack("N", $mva ? count($v) : $v);
                if ($mva) {
                    foreach ($v as $vv) {
                        $request.= pack("N", $vv);
                    }
                }
            }
        }

        // connect, send query, get response
        if (!($fp = $this->connect())) {
            $this->mbPop();

            return -1;
        }

        $len = strlen($request);
        $request= pack("nnN", SEARCHD_COMMAND_UPDATE, VER_COMMAND_UPDATE, $len) . $request; // add header
        if (!$this->send($fp, $request, $len + 8)) {
            $this->mbPop();

            return -1;
        }

        if (!($response = $this->getResponse($fp, VER_COMMAND_UPDATE))) {
            $this->mbPop();

            return -1;
        }

        // parse response
        list(, $updated) = unpack("N*", substr($response, 0, 4));
        $this->mbPop();

        return $updated;
    }

    /**
     * Opens the connection to searchd.
     *
     * @return bool
     */
    public function open()
    {
        if ($this->socket !== false) {
            $this->error = 'already connected';

            return false;
        }

        if (!$fp = $this->connect()) {
            return false;
        }

        // command, command version = 0, body length = 4, body = 1
        $request= pack("nnNN", SEARCHD_COMMAND_PERSIST, 0, 4, 1);
        if (!$this->send($fp, $request, 12)) {
            return false;
        }

        $this->socket = $fp;

        return true;
    }

    /**
     * Closes the connection to searchd.
     *
     * @return bool
     */
    public function close()
    {
        if ($this->socket === false) {
            $this->error = 'not connected';

            return false;
        }

        fclose($this->socket);
        $this->socket = false;

        return true;
    }

    /**
     * Checks the searchd status.
     * @return array|bool
     */
    public function status()
    {
        $this->mbPush();
        if (!($fp = $this->connect())) {
            $this->mbPop();

            return false;
        }

        $request= pack("nnNN", SEARCHD_COMMAND_STATUS, VER_COMMAND_STATUS, 4, 1); // len=4, body=1
        if (!($this->send($fp, $request, 12)) ||
            !($response = $this->getResponse($fp, VER_COMMAND_STATUS))
        ) {
            $this->mbPop();

            return false;
        }

        substr($response, 4); // just ignore length, error handling, etc
        $p = 0;
        list($rows, $cols) = array_values(unpack("N*N*", substr($response, $p, 8)));
        $p += 8;

        $res = array();
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                list(, $len) = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $res[$i][] = substr($response, $p, $len);
                $p += $len;
            }
        }

        $this->mbPop();

        return $res;
    }

    /**
     * @return int
     */
    public function flushAttributes()
    {
        $this->mbPush();
        if (!($fp = $this->connect())) {
            $this->mbPop();

            return -1;
        }

        $request= pack("nnN", SEARCHD_COMMAND_FLUSHATTRS, VER_COMMAND_FLUSHATTRS, 0); // len=0

        if (!($this->send($fp, $request, 8)) || !($response = $this->getResponse($fp, VER_COMMAND_FLUSHATTRS))) {
            $this->mbPop();

            return -1;
        }

        $tag = -1;

        if (strlen($response) == 4) {
            list(, $tag) = unpack("N*", $response);
        } else {
            $this->error = "unexpected response length";
        }

        $this->mbPop();

        return $tag;
    }

    /**
     * Removes a previously set filter.
     * @author: Nil Portugués Calderó <contact@nilportugues.com>
     *
     * @param $attribute
     * @return SphinxClient
     */
    public function removeFilter($attribute)
    {
        assert(is_string($attribute));
        foreach ($this->filters as $key => $filter) {
            if ($filter['attr'] == $attribute) {
                unset($this->filters[$key]);

                return $this;
            }
        }

        return $this;
    }

}
