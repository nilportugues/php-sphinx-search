<?php
/**
 * @author: Andrew Aksyonoff
 * @author: Nil Portugués <contact@nilportugues.com>
 *
 * Copyright (c) 2001-2012, Andrew Aksyonoff
 * Copyright (c) 2008-2012, Sphinx Technologies Inc
 *
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
use NilPortugues\Sphinx\Query\Query;
use NilPortugues\Sphinx\Searchd\Status;
use NilPortugues\Sphinx\Searchd\Command;
use NilPortugues\Sphinx\Searchd\Version;

/**
 * Class SphinxClient rewritten for modern PHP applications and code standards.
 *
 * @package NilPortugues\Sphinx
 * @author: Nil Portugués <contact@nilportugues.com>
 */
class SphinxClient
{
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
     * @var Query
     */
    private $query;

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
        $this->query = new Query();

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
     * @param  int          $port
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
     * @throws SphinxClientException
     * @return SphinxClient
     */
    public function setLimits($offset, $limit, $max = 0, $cutoff = 0)
    {
        $offset = (int) $offset;
        $limit= (int) $limit;
        $max = (int) $max;
        $cutoff = (int) $cutoff;

        if ($offset < 0) {
            throw new SphinxClientException('Offset must be an integer above zero');
        }

        if ($limit < 0) {
            throw new SphinxClientException('Limit must be an integer above zero');
        }

        if($max < 0) {
            throw new SphinxClientException('Max must be an integer above zero');
        }

        if($cutoff < 0) {
            throw new SphinxClientException('CutOff must be an integer above zero');
        }

        $this->offset = $offset;
        $this->limit = $limit;
        $this->maxMatches = $max;
        $this->cutOff = $cutoff;

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
     * @param  string       $rankExpr
     * @return SphinxClient
     */
    public function setRankingMode($ranker, $rankExpr = "")
    {
        $this->ranker->setRankingMode($ranker, $rankExpr);

        return $this;
    }

    /**
     * Set matches sorting mode.
     *
     * @param $mode
     * @param string $sortBy
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
     * @param  int   $min
     * @param  int   $max
     * @return $this
     */
    public function setIDRange($min, $max)
    {
       $this->query->setIDRange($min, $max);

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
        $this->filter->setFilter($attribute, $values, $exclude);

        return $this;
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
        $this->filter->setFilterRange($attribute, $min, $max, $exclude);

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
        $this->query->setGeoAnchor($attrlat, $attrlong, $lat, $long);

        return $this;
    }

    /**
     * Set grouping attribute and function.
     *
     * @param $attribute
     * @param $func
     * @param  string $groupsort
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
     * @param  int          $delay
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
        $this->arrayResult = (bool) $arrayResult;

        return $this;
    }

    /**
     * Overrides set attribute values. Attributes can be overridden one by one.
     * $values must be a hash that maps document IDs to attribute values.
     *
     * @param $attributeName
     * @param $attributeType
     * @param  array $values
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
        return $this->query->query($query, $index, $comment);
    }

    /**
     * Adds a query to a multi-query batch. Returns index into results array from RunQueries() call.
     *
     * @param $query
     * @param string $index
     * @param string $comment
     * @return int
     */
    public function addQuery($query, $index = "*", $comment = "")
    {
        return $this->query->addQuery($query, $index, $comment);
    }

    /**
     * connects to searchd, runs queries in batch, and returns an array of result sets.
     *
     * @return array|bool
     */
    public function runQueries()
    {
        if (empty($this->requests)) {
            $this->errorBag->setError("no queries defined, issue AddQuery() first");

            return false;
        }

        // mbstring workaround
        $this->multiByte->push();

        if (!($fp = $this->connection->connect())) {
            $this->multiByte->pop();

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
            $this->multiByte->pop();

            return false;
        }

        // query sent ok; we can reset reqs now
        $this->requests = array();

        // parse and return response
        return $this->response->parseSearchResponse($response, $nreqs);
    }














    /**
     * Connects to Searchd server, and generate excerpts (snippets) of given documents for given query.
     * Returns false on failure, or an array of snippets on success.
     *
     * @param  array $docs
     * @param $index
     * @param $words
     * @param  array $options
     * @return array
     */
    public function buildExcerpts(array $docs, $index, $words, array $options = array())
    {
        $index = (string) $index;
        $words = (string) $words;

        $this->multiByte->push();

        if (!($fp = $this->connection->connect())) {
            $this->multiByte->pop();

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
            $this->multiByte->pop();

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
     * @param  array  $options
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
     * @param  array  $options
     * @return string
     */
    private function buildRequestOptions(array &$options)
    {
        $options["limit"] = (int) $options["limit"];
        $options["around"] = (int) $options["around"];
        $options["limit_passages"] = (int) $options["limit_passages"];
        $options["limit_words"] = (int) $options["limit_words"];
        $options["start_passage_id"] = (int) $options["start_passage_id"];

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
                $this->errorBag->setError("incomplete reply");
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
        $req .= pack("N", (int) $hits);

        ////////////////////////////
        // send query, get response
        ////////////////////////////

        $len = strlen($req);
        $req = pack("nnN", Command::KEYWORDS, Version::KEYWORDS, $len) . $req; // add header
        if (!($this->connection->send($fp, $req, $len + 8)) ||
            !($response = $this->response->getResponse($fp, Version::KEYWORDS))
        ) {
            $this->multiByte->pop();

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

                $this->errorBag->setError("incomplete reply");
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
     * @param  bool  $mva
     * @return int
     */
    public function updateAttributes($index, array $attributes, array $values, $mva = false)
    {
        $mva = (bool) $mva;
        $index = (string) $index;

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
            $this->multiByte->pop();

            return -1;
        }

        $tag = -1;
        if (strlen($response) == 4)
            list(, $tag) = unpack("N*", $response);
        else
            $this->errorBag->setError("unexpected response length");

        $this->multiByte->pop();

        return $tag;
    }
}
