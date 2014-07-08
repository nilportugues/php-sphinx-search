<?php

namespace NilPortugues\Sphinx\Query;

use NilPortugues\Sphinx\Client\ErrorBag;
use NilPortugues\Sphinx\Filter\Filter;
use NilPortugues\Sphinx\Fulltext\Ranker;
use NilPortugues\Sphinx\Helpers\MultiByte;
use NilPortugues\Sphinx\Helpers\Packer;
use NilPortugues\Sphinx\Searchd\Status;

/**
 * Class Query
 * @package NilPortugues\Sphinx\Query
 */
class Query
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
     * @var MultiByte
     */
    private $multiByte;

    /**
     * @var Packer
     */
    private $packer;

    /**
     * @var ErrorBag
     */
    private $errorBag;

    /**
     * @var Ranker
     */
    private $ranker;

    /**
     * @param MultiByte $multiByte
     * @param Packer $packer
     * @param ErrorBag $errorBag
     * @param Ranker $ranker
     */
    public function __construct(MultiByte $multiByte, Packer $packer, ErrorBag $errorBag, Ranker $ranker)
    {
        $this->multiByte = $multiByte;
        $this->packer = $packer;
        $this->errorBag = $errorBag;
        $this->ranker = $ranker;
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
        assert($min <= $max);

        $this->minId = (int) $min;
        $this->maxId = (int) $max;

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
     * @return $this
     */
    public function setGeoAnchor($attrlat, $attrlong, $lat, $long)
    {
        $this->anchor = array(
            "attrlat" => (string) $attrlat,
            "attrlong" => (string) $attrlong,
            "lat" => (float) $lat,
            "long" => (float) $long
        );

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

        if (!is_array($results)) {
            return false; // probably network error; error message should be already filled
        }

        $this->errorBag->setError($results[0]["error"]);
        $this->errorBag->setWarning($results[0]["warning"]);

        if ($results[0]["status"] == Status::ERROR) {
            return false;
        } else {
            return $results[0];
        }
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
        $this->multiByte->push();

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
            $req .= pack("N", (int) $weight);
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
        $this->multiByte->pop();

        // store request to requests array
        $this->requests[] = $req;

        return count($this->requests) - 1;
    }
}
 