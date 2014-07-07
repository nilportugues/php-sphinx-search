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

use NilPortugues\Sphinx\Client\Connection;
use NilPortugues\Sphinx\Client\ErrorBag;
use NilPortugues\Sphinx\Client\Response;
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
    /**
     * How many records to seek from result-set start (default is 0)
     *
     * @var int
     */
    private $offset = 0;

    /**
     * How many records to return from result-set starting at offset (default is 20)
     *
     * @var int
     */
    private $limit = 20;


    /**
     * Min ID to match (default is 0, which means no limit)
     *
     * @var int
     */
    private $minId = 0;

    /**
     * Max ID to match (default is 0, which means no limit)
     *
     * @var int
     */
    private $maxId = 0;

    /**
     * Max matches to retrieve
     *
     * @var int
     */
    private $maxMatches = 1500;

    /**
     * Cutoff to stop searching at (default is 0)
     *
     * @var int
     */
    private $cutOff = 0;


    /**
     * Geographical anchor point
     *
     * @var array
     */
    private $anchor;

    /**
     *  Whether $result["matches"] should be a hash or an array
     *
     * @var bool
     */
    private $arrayResult = false;





    //These below should be kept. Above privates belong somewhere else.




    /**
     * @var Packer
     */
    private $packer;

    /**
     * @var MultiByte
     */
    private $multiByte;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Attribute
     */
    private $queryAttribute;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var ErrorBag
     */
    private $errorBag;

    /**
     * Creates a new client object and fill defaults
     */
    public function __construct()
    {
        $this->packer = new Packer();
        $this->multiByte = new MultiByte();
        $this->errorBag = new ErrorBag();

        $this->connection = new Connection($this->packer, $this->multiByte, $this->errorBag);
        $this->response = $this->connection->getResponse();
        $this->request = $this->connection->getRequest();

        $this->ranker = new Ranker();
        $this->filter = new Filter();
        $this->sorter = new Sorter();
        $this->matcher = new Matcher();
        $this->queryGroupBy = new GroupBy();
        $this->queryAttribute = new Attribute();

    }

    /**
     * Closes Sphinx socket.
     */
    public function __destruct()
    {
        $this->connection->closeSocket();
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
        $this->connection->setServer((string) $host, (int) $port);

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
        $this->connection->setConnectTimeout((int) $timeout);

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
        assert($offset >= 0);
        assert($limit > 0);
        assert($max >= 0);
        assert($cutoff >= 0);

        $this->offset = (int)$offset;
        $this->limit = (int)$limit;

        if ($max > 0) {
            $this->maxMatches = (int)$max;
        }

        if ($cutoff > 0) {
            $this->cutOff = (int)$cutoff;
        }

        return $this;
    }



    /**
     * Sets a matching mode.
     *
     * @param int $mode
     *
     * @throws SphinxClientException
     * @return SphinxClient
     */
    public function setMatchMode($mode)
    {
        $this->matcher->setMode($mode);

        return $this;
    }

    /**
     * Sets ranking mode.
     *
     * @param $ranker
     * @param  string $rankExpr
     * @return SphinxClient
     */
    public function setRankingMode($ranker, $rankExpr = "")
    {
        assert($ranker === 0 || $ranker >= 1 && $ranker < Ranker::TOTAL);

        $this->ranker->setRanker((int) $ranker);
        $this->ranker->setRankExpr((string) $rankExpr);

        return $this;
    }

    /**
     * Set matches sorting mode.
     *
     * @param $mode
     * @param  string $sortBy
     *
     * @throws SphinxClientException
     * @return SphinxClient
     */
    public function setSortMode($mode, $sortBy = "")
    {
        $this->sorter->setSortMode($mode, $sortBy);

        return $this;
    }


    /**
     * Set IDs range to match. Only match records if document ID is between $min and $max (inclusive)
     *
     * @param int $min
     * @param int $max
     * @return $this
     */
    public function setIDRange($min, $max)
    {
        assert($min <= $max);

        $this->minId = (int)$min;
        $this->maxId = (int)$max;

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
        $this->filter->setFilter($attribute, $values, $exclude);

        return $this;
    }


    /**
     * @param $attribute
     * @param $min
     * @param $max
     * @param bool $exclude
     * @return $this
     */
    public function setFilterRange($attribute, $min, $max, $exclude = false)
    {
        $this->filter->setFilterRange($attribute, $min, $max, $exclude);

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
        $this->filter->setFilterFloatRange($attribute, $min, $max, $exclude);

        return $this;
    }

    /**
     * Set ups anchor point for geosphere distance calculations required
     * to use @geodist in filters and sorting latitude and longitude must be in radians.
     *
     * @param $attrlat
     * @param $attrlong
     * @param $lat
     * @param $long
     * @return SphinxClient
     */
    public function setGeoAnchor($attrlat, $attrlong, $lat, $long)
    {

        $this->anchor = array(
            "attrlat" => (string)$attrlat,
            "attrlong" => (string)$attrlong,
            "lat" => (float)$lat,
            "long" => (float)$long
        );

        return $this;
    }

    /**
     * Set grouping attribute and function.
     *
     * @param $attribute
     * @param $func
     * @param string $groupsort
     * @return $this
     */
    public function setGroupBy($attribute, $func, $groupsort = "@group desc")
    {
        $this->queryGroupBy->setGroupBy($attribute, $func, $groupsort);

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
        $this->queryGroupBy->setGroupDistinct($attribute);

        return $this;
    }

    /**
     * Sets distributed retries count and delay values.
     *
     * @param $count
     * @param  int $delay
     * @return SphinxClient
     */
    public function setRetries($count, $delay = 0)
    {
        $this->connection->setRetries($count, $delay);

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
        $this->arrayResult = (bool)$arrayResult;

        return $this;
    }

    /**
     * Overrides set attribute values. Attributes can be overridden one by one.
     * $values must be a hash that maps document IDs to attribute values.
     *
     * @param $attributeName
     * @param $attributeType
     * @param array $values
     * @return $this
     */
    public function setOverride($attributeName, $attributeType, array $values)
    {
        $this->queryAttribute->setOverride($attributeName, $attributeType, $values);

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
        $this->select = (string) $select;

        return $this;
    }

    /**
     * Clears all filters (for multi-queries).
     *
     * @return SphinxClient
     */
    public function resetFilters()
    {
        $this->filter->reset();

        return $this;
    }

    /**
     * Clears groupby settings (for multi-queries)
     *
     * @return SphinxClient
     */
    public function resetGroupBy()
    {
        $this->queryGroupBy->reset();

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
     * connects to searchd server, run given search query through given indexes, and returns the search results.
     *
     * @param $query
     * @param  string $index
     * @param  string $comment
     * @return bool
     */
    public function query($query, $index = "*", $comment = "")
    {
        assert(empty($this->requests));

        $this->AddQuery($query, $index, $comment);
        $results = $this->RunQueries();
        $this->requests = array(); // just in case it failed too early

        if (!is_array($results))
            return false; // probably network error; error message should be already filled

        $this->error = $results[0]["error"];
        $this->warning = $results[0]["warning"];
        if ($results[0]["status"] == Status::ERROR)
            return false;
        else
            return $results[0];
    }

    /**
     * @TODO: NEEDS TO REPLACE ALMOST EVERY PRIVATE VAR REFERENCE FOR THE OBJECT GETTER OR SETTER.
     *
     *
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
        $req = pack("NNNN", $this->offset, $this->limit, $this->mode, $this->ranker);
        if ($this->ranker->getRanker() == Ranker::EXPR) {
            $req .= pack("N", strlen($this->ranker->getRankExpr())) . $this->ranker->getRankExpr();
        }

        $req .= pack("N", $this->sort); // (deprecated) sort mode
        $req .= pack("N", strlen($this->sortBy)) . $this->sortBy;
        $req .= pack("N", strlen($query)) . $query; // query itself
        $req .= pack("N", count($this->weights)); // weights
        foreach ($this->weights as $weight)
            $req .= pack("N", (int)$weight);
        $req .= pack("N", strlen($index)) . $index; // indexes
        $req .= pack("N", 1); // id64 range marker
        $req .= $this->packer->sphPackU64($this->minId) . $this->packer->sphPackU64($this->maxId); // id64 range

        // filters
        $req .= pack("N", count($this->filters));
        foreach ($this->filters as $filter) {
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

                case Filter::FLOAT_RANGE:
                    $req .= $this->packer->packFloat($filter["min"]) . $this->packer->packFloat($filter["max"]);
                    break;

                default:
                    assert(0 && "internal error: unhandled filter type");
            }
            $req .= pack("N", $filter["exclude"]);
        }

        // group-by clause, max-matches count, group-sort clause, cutoff count
        $req .= pack("NN", $this->groupFunc, strlen($this->groupBy)) . $this->groupBy;
        $req .= pack("N", $this->maxMatches);
        $req .= pack("N", strlen($this->groupSort)) . $this->groupSort;
        $req .= pack("NNN", $this->cutOff, $this->retryCount, $this->retryDelay);
        $req .= pack("N", strlen($this->groupDistinct)) . $this->groupDistinct;

        // anchor point
        if (empty($this->anchor)) {
            $req .= pack("N", 0);
        } else {
            $a =& $this->anchor;
            $req .= pack("N", 1);
            $req .= pack("N", strlen($a["attrlat"])) . $a["attrlat"];
            $req .= pack("N", strlen($a["attrlong"])) . $a["attrlong"];
            $req .= $this->packer->packFloat($a["lat"]) . $this->packer->packFloat($a["long"]);
        }

        // per-index weights
        $req .= pack("N", count($this->indexWeights));
        foreach ($this->indexWeights as $idx => $weight)
            $req .= pack("N", strlen($idx)) . $idx . pack("N", $weight);

        // max query time
        $req .= pack("N", $this->maxQueryTime);

        // per-field weights
        $req .= pack("N", count($this->fieldWeights));
        foreach ($this->fieldWeights as $field => $weight)
            $req .= pack("N", strlen($field)) . $field . pack("N", $weight);

        // comment
        $req .= pack("N", strlen($comment)) . $comment;

        // attribute overrides
        $req .= pack("N", count($this->overrides));
        foreach ($this->overrides as $entry) {
            $req .= pack("N", strlen($entry["attr"])) . $entry["attr"];
            $req .= pack("NN", $entry["type"], count($entry["values"]));
            foreach ($entry["values"] as $id => $val) {
                assert(is_numeric($id));
                assert(is_numeric($val));

                $req .= $this->packer->sphPackU64($id);
                switch ($entry["type"]) {
                    case Attribute::FLOAT:
                        $req .= $this->packer->packFloat($val);
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
        $req .= pack("N", strlen($this->select)) . $this->select;

        // mbstring workaround
        $this->multiByte->Pop();

        // store request to requests array
        $this->requests[] = $req;

        return count($this->requests) - 1;
    }

    /**
     * connects to searchd, runs queries in batch, and returns an array of result sets.
     *
     * @return array|bool
     */
    public function runQueries()
    {
        if (empty($this->requests)) {
            $this->error = "no queries defined, issue AddQuery() first";
            return false;
        }

        // mbstring workaround
        $this->multiByte->Push();

        if (!($fp = $this->connection->connect())) {
            $this->multiByte->Pop();
            return false;
        }

        // send query, get response
        $nreqs = count($this->requests);
        $req = join("", $this->requests);
        $len = 8 + strlen($req);
        $req = pack("nnNNN", Command::SEARCH, Version::SEARCH, $len, 0, $nreqs) . $req; // add header

        if (!($this->connection->send($fp, $req, $len + 8)) ||
            !($response = $this->response->getResponse($fp, Version::SEARCH))
        ) {
            $this->multiByte->Pop();

            return false;
        }

        // query sent ok; we can reset reqs now
        $this->requests = array();

        // parse and return response
        return $this->_ParseSearchResponse($response, $nreqs);
    }

    /**
     * Connects to Searchd server, and generate excerpts (snippets) of given documents for given query.
     * Returns false on failure, or an array of snippets on success.
     *
     * @param array $docs
     * @param $index
     * @param $words
     * @param array $options
     * @return array
     */
    public function buildExcerpts(array $docs, $index, $words, array $options = array())
    {
        $index = (string) $index;
        $words = (string) $words;

        $this->multiByte->Push();

        if (!($fp = $this->connection->connect())) {
            $this->multiByte->Pop();
            return array();
        }

        $defaultOptions = $this->defaultOptions();
        $options = array_filter($options);
        $options = $options + $defaultOptions;

        $requestBody = $this->buildRequestFlags($options)
            . $this->buildRequestIndex($index)
            . $this->buildRequestWords($words)
            . $this->buildRequestOptions($options)
            . $this->buildRequestDocuments($docs);

        $requestMessage = $this->buildRequest($requestBody);

        if (!($this->connection->send($fp, $requestMessage, strlen($requestBody) + 8)) ||
            !($response = $this->response->getResponse($fp, Version::EXCERPT))
        ) {
            $this->multiByte->Pop();
            return array();
        }

        return $this->parseRequestResponse($response, $docs);
    }

    /**
     * @return array
     */
    private function defaultOptions()
    {
        return array(
            "before_match" => "<b>",
            "after_match" => "</b>",
            "chunk_separator" => " ... ",
            "limit" => 256,
            "limit_passages" => 0,
            "limit_words" => 0,
            "around" => 5,
            "exact_phrase" => false,
            "single_passage" => false,
            "use_boundaries" => false,
            "weight_order" => false,
            "query_mode" => false,
            "force_all_words" => false,
            "start_passage_id" => 1,
            "load_files" => false,
            "html_strip_mode" => "index",
            "allow_empty" => false,
            "passage_boundary" => "none",
            "emit_zones" => false,
            "load_files_scattered" => false,
        );
    }

    /**
     * @param array $options
     * @return string
     */
    private function buildRequestFlags(array &$options)
    {
        $flags = 1;
        $optionKeys = array_keys($options);

        foreach ($optionKeys as $keyName) {
            switch ($keyName) {
                case "exact_phrase":
                    $flags |= 2;
                    break;
                case "single_passage":
                    $flags |= 4;
                    break;
                case "use_boundaries":
                    $flags |= 8;
                    break;
                case "weight_order":
                    $flags |= 16;
                    break;
                case "query_mode":
                    $flags |= 32;
                    break;
                case "force_all_words":
                    $flags |= 64;
                    break;
                case "load_files":
                    $flags |= 128;
                    break;
                case "allow_empty":
                    $flags |= 256;
                    break;
                case "emit_zones":
                    $flags |= 512;
                    break;
                case "load_files_scattered":
                    $flags |= 1024;
                    break;
            }
        }

        $request = pack("NN", 0, $flags); // mode=0, flags=$flags

        return $request;
    }

    /**
     * @param $index
     * @return string
     */
    private function buildRequestIndex($index)
    {
        return pack("N", strlen($index)) . $index;
    }

    /**
     * @param $words
     * @return string
     */
    private function buildRequestWords($words)
    {
        return pack("N", strlen($words)) . $words;
    }

    /**
     * @param array $options
     * @return string
     */
    private function buildRequestOptions(array &$options)
    {
        $options["limit"] = (int)$options["limit"];
        $options["around"] = (int)$options["around"];
        $options["limit_passages"] = (int)$options["limit_passages"];
        $options["limit_words"] = (int)$options["limit_words"];
        $options["start_passage_id"] = (int)$options["start_passage_id"];

        $request = pack("N", strlen($options["before_match"]))
            . $options["before_match"]
            . pack("N", strlen($options["after_match"]))
            . $options["after_match"]
            . pack("N", strlen($options["chunk_separator"]))
            . $options["chunk_separator"]
            . pack("NN", $options["limit"], $options["around"])
            . pack("NNN", $options["limit_passages"], $options["limit_words"], $options["start_passage_id"])
            . $options["html_strip_mode"]
            . pack("N", strlen($options["passage_boundary"]))
            . $options["passage_boundary"];

        return $request;
    }

    /**
     * @param $docs
     * @return string
     */
    private function buildRequestDocuments($docs)
    {
        $request = '';
        pack("N", count($docs));

        foreach ($docs as $doc) {
            assert(is_string($doc));
            $request .= pack("N", strlen($doc)) . $doc;
        }
        return $request;
    }

    /**
     * @param $requestBody
     * @return string
     */
    private function buildRequest($requestBody)
    {
        $messageLength = strlen($requestBody);
        return pack("nnN", Command::EXCERPT, Version::EXCERPT, $messageLength) . $requestBody;
    }

    /**
     * @param $response
     * @param $docs
     * @return array
     */
    private function parseRequestResponse($response, $docs)
    {
        $pos = 0;
        $res = array();
        $rlen = strlen($response);

        for ($i = 0; $i < count($docs); $i++) {
            list(, $len) = unpack("N*", substr($response, $pos, 4));
            $pos += 4;

            if ($pos + $len > $rlen) {
                $this->error = "incomplete reply";
                $this->multiByte->pop();
                return array();
            }

            $res[] = $len ? substr($response, $pos, $len) : "";
            $pos += $len;
        }

        $this->multiByte->pop();

        return $res;
    }

    /**
     * connects to searchd server, and generates a keyword list for a given query.
     * Returns false on failure or an array of words on success.
     *
     * @param $query
     * @param $index
     * @param $hits
     * @return array
     */
    public function buildKeywords($query, $index, $hits)
    {
        $query = (string) $query;
        $index = (string) $index;
        $hits = (bool) $hits;

        $this->multiByte->push();

        if (!($fp = $this->connection->connect())) {
            $this->multiByte->pop();
            return array();
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
        if (!($this->connection->send($fp, $req, $len + 8)) ||
            !($response = $this->response->getResponse($fp, Version::KEYWORDS))
        ) {
            $this->multiByte->Pop();

            return array();
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
                $this->multiByte->pop();
                return array();
            }
        }

        $this->multiByte->pop();

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
     * @param  bool $mva
     * @return int
     */
    public function updateAttributes($index, array $attributes, array $values, $mva = false)
    {
        $mva = (bool)$mva;
        $index = (string)$index;


        assert(is_array($attributes));
        foreach ($attributes as $attribute) {
            assert(is_string($attribute));
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
        $this->multiByte->push();
        $req = pack("N", strlen($index)) . $index;

        $req .= pack("N", count($attributes));
        foreach ($attributes as $attribute) {
            $req .= pack("N", strlen($attribute)) . $attribute;
            $req .= pack("N", $mva ? 1 : 0);
        }

        $req .= pack("N", count($values));
        foreach ($values as $id => $entry) {

            $req .= $this->packer->sphPackU64($id);

            foreach ($entry as $v) {
                $req .= pack("N", $mva ? count($v) : $v);
                if ($mva) {
                    foreach ($v as $vv) {
                        $req .= pack("N", $vv);
                    }
                }
            }
        }

        // connect, send query, get response
        if (!($fp = $this->connection->connect())) {
            $this->multiByte->pop();
            return -1;
        }

        $len = strlen($req);
        $req = pack("nnN", Command::UPDATE, Version::UPDATE, $len) . $req; // add header

        if (!$this->connection->send($fp, $req, $len + 8)) {
            $this->multiByte->pop();
            return -1;
        }

        if (!($response = $this->response->getResponse($fp, Version::UPDATE))) {
            $this->multiByte->pop();
            return -1;
        }

        // parse response
        list(, $updated) = unpack("N*", substr($response, 0, 4));
        $this->multiByte->pop();

        return $updated;
    }

    /**
     * @return int
     */
    public function flushAttributes()
    {
        $this->multiByte->push();
        if (!($fp = $this->connection->connect())) {
            $this->multiByte->pop();

            return -1;
        }

        $req = pack("nnN", Command::FLUSH_ATTRS, Version::FLUSHATTRS, 0); // len=0
        if (!($this->connection->send($fp, $req, 8)) ||
            !($response = $this->response->getResponse($fp, Version::FLUSHATTRS))
        ) {
            $this->multiByte->Pop();

            return -1;
        }

        $tag = -1;
        if (strlen($response) == 4)
            list(, $tag) = unpack("N*", $response);
        else
            $this->error = "unexpected response length";

        $this->multiByte->pop();

        return $tag;
    }
}
