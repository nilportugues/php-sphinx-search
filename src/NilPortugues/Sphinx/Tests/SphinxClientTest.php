<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 4/24/14
 * Time: 1:45 AM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Sphinx\Tests;

use \NilPortugues\Sphinx\SphinxClient;

/**
 * Testing the SphinxClient class.
 * This does not test the search results returned by searchd.
 *
 * Class SphinxClientTest
 * @package NilPortugues\Sphinx\Tests
 */
class SphinxClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SphinxClient
     */
    protected $sphinx;
    /**
     * @var string
     */
    protected $instanceName;

    /**
     *
     */
    public function setUp()
    {
        $this->instanceName = '\NilPortugues\Sphinx\SphinxClient';

        $this->sphinx = new SphinxClient();
        $this->sphinx->setServer(SPHINX_HOST, SPHINX_PORT);
        $this->sphinx->query('Spider-Man', 'movies');
        $this->sphinx->open();
    }

    /**
     *
     */
    public function testRemoveFilter()
    {
        $this->sphinx->setFilter('year', array(2014));
        $reflectionClass = new \ReflectionClass($this->sphinx);

        $filters = $reflectionClass->getProperty('filters');
        $filters->setAccessible(true);
        $filters = $filters->getValue($this->sphinx);
        $filters = $filters[0];

        $this->assertInternalType('array', $filters);
        $this->assertEquals('year', $filters['attr']);
        $this->assertEquals(array(2014), $filters['values']);

        $instance = $this->sphinx->removeFilter('year');

        $this->assertInstanceOf($this->instanceName, $instance);

        $filters = $reflectionClass->getProperty('filters');
        $filters->setAccessible(true);
        $filters = $filters->getValue($this->sphinx);

        $this->assertInternalType('array', $filters);
        $this->assertEquals(array(), $filters);
    }

    /**
     *
     */
    public function testGetLastError()
    {
        $this->sphinx = new SphinxClient();
        $this->assertEquals('', $this->sphinx->getLastError());
    }

    /**
     *
     */
    public function testGetLastErrorWhenConnectingToNonExistentHost()
    {
        $this->sphinx->close();
        $this->sphinx
            ->setServer('2013.192.168.0.1')
            ->query('Spider-Man');

        $this->assertEquals
        (
            'connection to 2013.192.168.0.1:3312 failed (errno=0, msg=php_network_getaddresses: getaddrinfo failed: Name or service not known)',
            $this->sphinx->getLastError()
        );
    }

    /**
     *
     */
    public function testGetLastErrorWhenConnectionNotEstablished()
    {
        $this->sphinx->close();
        $this->sphinx = new SphinxClient();
        $this->sphinx->setServer(SPHINX_HOST, 6666);
        $this->sphinx->query('Spider-Man');
        $this->sphinx->open();
        $this->assertNotEmpty($this->sphinx->getLastError());
    }

    /**
     *
     */
    public function testIsConnectErrorNoConnectionInitialized()
    {
        $this->sphinx = new SphinxClient();
        $actual = $this->sphinx->isConnectError();
        $this->assertFalse($actual);
    }

    /**
     *
     */
    public function testIsConnectErrorWhenConnectionInitializedWithWrongData()
    {
        $this->sphinx->close();
        $this->sphinx
            ->setServer('0.0.0.1', SPHINX_PORT)
            ->query('test');
        $actual = $this->sphinx->isConnectError();
        $this->assertTrue($actual);
    }

    /**
     *
     */
    public function testSetServerHostAndPort()
    {
        $instance = $this->sphinx->setServer(SPHINX_HOST, 80);
        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $path = $reflectionClass->getProperty('path');
        $path->setAccessible(true);

        $port = $reflectionClass->getProperty('port');
        $port->setAccessible(true);

        $this->assertEquals('', $path->getValue($this->sphinx));
        $this->assertEquals(80, $port->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetServerHostOnly()
    {
        $instance = $this->sphinx->setServer(SPHINX_HOST);
        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $path = $reflectionClass->getProperty('path');
        $path->setAccessible(true);

        $port = $reflectionClass->getProperty('port');
        $port->setAccessible(true);

        $this->assertEquals('', $path->getValue($this->sphinx));
        $this->assertEquals(3312, $port->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetConnectTimeout()
    {
        $instance = $this->sphinx->setConnectTimeout(10);
        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $timeout = $reflectionClass->getProperty('timeout');
        $timeout->setAccessible(true);
        $this->assertEquals(10, $timeout->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetConnectTimeoutErrorOffsetBelowZero()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setConnectTimeout(-10);
    }

    /**
     *
     */
    public function testSetLimits()
    {
        $instance = $this->sphinx->setLimits(10, 100, 1000, 500);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $offset = $reflectionClass->getProperty('offset');
        $offset->setAccessible(true);

        $limit = $reflectionClass->getProperty('limit');
        $limit->setAccessible(true);

        $maxMatches = $reflectionClass->getProperty('maxMatches');
        $maxMatches->setAccessible(true);

        $cutOff = $reflectionClass->getProperty('cutOff');
        $cutOff->setAccessible(true);

        $this->assertEquals(10, $offset->getValue($this->sphinx));
        $this->assertEquals(100, $limit->getValue($this->sphinx));
        $this->assertEquals(1000, $maxMatches->getValue($this->sphinx));
        $this->assertEquals(500, $cutOff->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetLimitsErrorOffsetBelowZero()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setLimits(-10, 100, 1000, 500);
    }

    /**
     *
     */
    public function testSetLimitsErrorLimitBelowZero()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setLimits(10, -100, 1000, 500);
    }

    /**
     *
     */
    public function testSetLimitsErrorMaxMatchesBelowZero()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setLimits(10, 100, -1000, 500);
    }

    /**
     *
     */
    public function testSetLimitsErrorCutOffBelowZero()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setLimits(10, 100, 1000, -500);
    }

    /**
     *
     */
    public function testSetMaxQueryTime()
    {
        $instance = $this->sphinx->setMaxQueryTime(10);
        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $maxQueryTime = $reflectionClass->getProperty('maxQueryTime');
        $maxQueryTime->setAccessible(true);

        $this->assertEquals(10, $maxQueryTime->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetMaxQueryErrorTimeBelowZero()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setMaxQueryTime(-10);
    }

    /**
     *
     */
    public function testSetMaxQueryErrorIsNotInteger()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setMaxQueryTime(null);
    }

    /**
     *
     */
    public function testSetMatchModeErrorValueIsNotValid()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setMatchMode(100000000);
    }

    /**
     *
     */
    public function testSetMatchMode_SPH_MATCH_ALL()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_ALL);
        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $mode = $reflectionClass->getProperty('mode');
        $mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_ALL, $mode->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetMatchMode_SPH_MATCH_ANY()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_ANY);
        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $mode = $reflectionClass->getProperty('mode');
        $mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_ANY, $mode->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetMatchMode_SPH_MATCH_PHRASE()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_PHRASE);
        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $mode = $reflectionClass->getProperty('mode');
        $mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_PHRASE, $mode->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetMatchMode_SPH_MATCH_BOOLEAN()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_BOOLEAN);
        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $mode = $reflectionClass->getProperty('mode');
        $mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_BOOLEAN, $mode->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetMatchMode_SPH_MATCH_EXTENDED()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_EXTENDED);
        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $mode = $reflectionClass->getProperty('mode');
        $mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_EXTENDED, $mode->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetMatchMode_SPH_MATCH_FULLSCAN()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_FULLSCAN);
        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $mode = $reflectionClass->getProperty('mode');
        $mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_FULLSCAN, $mode->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetMatchMode_SPH_MATCH_EXTENDED2()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_EXTENDED2);
        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $mode = $reflectionClass->getProperty('mode');
        $mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_EXTENDED2, $mode->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetRankingMode_SPH_RANK_EXPR()
    {
        $instance = $this->sphinx->setRankingMode(SPH_RANK_EXPR);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $ranker = $reflectionClass->getProperty('ranker');
        $ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_EXPR, $ranker->getValue($this->sphinx));

    }

    /**
     *
     */
    public function testSetRankingMode_SPH_RANK_FIELDMASK()
    {
        $instance = $this->sphinx->setRankingMode(SPH_RANK_FIELDMASK);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $ranker = $reflectionClass->getProperty('ranker');
        $ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_FIELDMASK, $ranker->getValue($this->sphinx));

    }

    /**
     *
     */
    public function testSetRankingMode_SPH_RANK_MATCHANY()
    {
        $instance = $this->sphinx->setRankingMode(SPH_RANK_MATCHANY);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $ranker = $reflectionClass->getProperty('ranker');
        $ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_MATCHANY, $ranker->getValue($this->sphinx));

    }

    /**
     *
     */
    public function testSetRankingMode_SPH_RANK_WORDCOUNT()
    {
        $instance = $this->sphinx->setRankingMode(SPH_RANK_WORDCOUNT);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $ranker = $reflectionClass->getProperty('ranker');
        $ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_WORDCOUNT, $ranker->getValue($this->sphinx));

    }

    /**
     *
     */
    public function testSetRankingMode_SPH_RANK_PROXIMITY()
    {
        $instance = $this->sphinx->setRankingMode(SPH_RANK_PROXIMITY);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $ranker = $reflectionClass->getProperty('ranker');
        $ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_PROXIMITY, $ranker->getValue($this->sphinx));

    }

    /**
     *
     */
    public function testSetRankingMode_SPH_RANK_PROXIMITY_BM25()
    {
        $instance = $this->sphinx->setRankingMode(SPH_RANK_PROXIMITY_BM25);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $ranker = $reflectionClass->getProperty('ranker');
        $ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_PROXIMITY_BM25, $ranker->getValue($this->sphinx));

    }

    /**
     *
     */
    public function testSetRankingMode_SPH_RANK_BM25()
    {
        $instance = $this->sphinx->setRankingMode(SPH_RANK_BM25);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $ranker = $reflectionClass->getProperty('ranker');
        $ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_BM25, $ranker->getValue($this->sphinx));

    }

    /**
     *
     */
    public function testSetRankingMode_SPH_RANK_NONE()
    {
        $instance = $this->sphinx->setRankingMode(SPH_RANK_NONE);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $ranker = $reflectionClass->getProperty('ranker');
        $ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_NONE, $ranker->getValue($this->sphinx));

    }

    /**
     *
     */
    public function testSetRankingMode_SPH_RANK_SPH04()
    {
        $instance = $this->sphinx->setRankingMode(SPH_RANK_SPH04, "Some valid rank expression");

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $ranker = $reflectionClass->getProperty('ranker');
        $ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_SPH04, $ranker->getValue($this->sphinx));

        $rankExpr = $reflectionClass->getProperty('rankExpr');
        $rankExpr->setAccessible(true);
        $this->assertEquals("Some valid rank expression", $rankExpr->getValue($this->sphinx));

    }

    /**
     *
     */
    public function testSetRankingModeErrorValueIsNotValid()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setRankingMode(100000000);
    }

    /**
     *
     */
    public function testSetRankingModeErrorAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setRankingMode(SPH_RANK_SPH04, null);
    }

    /**
     *
     */
    public function testSetWeights()
    {
        $this->setExpectedException('\Exception');
        $weights = array
        (
            'index_attribute1' => 10,
            'index_attribute2' => 100,
        );

        $this->sphinx->setWeights($weights);
    }

    /**
     *
     */
    public function testSetFieldWeights()
    {
        $weights = array
        (
            'index_attribute1' => 10,
            'index_attribute2' => 100,
        );
        $instance = $this->sphinx->SetFieldWeights($weights);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $fieldWeights = $reflectionClass->getProperty('fieldWeights');
        $fieldWeights->setAccessible(true);

        $this->assertEquals($weights, $fieldWeights->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetIndexWeights()
    {
        $weights = array
        (
            'fulltext_attribute1' => 10,
            'fulltext_attribute2' => 100,
        );
        $instance = $this->sphinx->SetIndexWeights($weights);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $indexWeights = $reflectionClass->getProperty('indexWeights');
        $indexWeights->setAccessible(true);

        $this->assertEquals($weights, $indexWeights->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetSortModeErrorValueIsNotValid()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setSortMode(100000000);
    }

    /**
     *
     */
    public function testSetSortModeErrorAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setSortMode(SPH_SORT_ATTR_DESC, null);
    }

    /**
     *
     */
    public function testSetSortModeErrorModeIsTextStringDefaultsTo_SPH_SORT_RELEVANCE()
    {
        $instance = $this->sphinx->setSortMode("SPH_SORT_ATTR_DESC");

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $sort = $reflectionClass->getProperty('sort');
        $sort->setAccessible(true);
        $sortBy = $reflectionClass->getProperty('sortBy');
        $sortBy->setAccessible(true);

        $this->assertNotEquals(SPH_SORT_ATTR_DESC, $sort->getValue($this->sphinx));
        $this->assertEquals(SPH_SORT_RELEVANCE, $sort->getValue($this->sphinx));
        $this->assertEquals("", $sortBy->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetSortMode_SPH_SORT_RELEVANCE()
    {
        $instance = $this->sphinx->setSortMode(SPH_SORT_RELEVANCE);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $sort = $reflectionClass->getProperty('sort');
        $sort->setAccessible(true);
        $sortBy = $reflectionClass->getProperty('sortBy');
        $sortBy->setAccessible(true);

        $this->assertEquals(SPH_SORT_RELEVANCE, $sort->getValue($this->sphinx));
        $this->assertEquals("", $sortBy->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetSortMode_SPH_SORT_EXPR()
    {
        $instance = $this->sphinx->setSortMode(SPH_SORT_EXPR, "@weight + fulltext_field*200");

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $sort = $reflectionClass->getProperty('sort');
        $sort->setAccessible(true);
        $sortBy = $reflectionClass->getProperty('sortBy');
        $sortBy->setAccessible(true);

        $this->assertEquals(SPH_SORT_EXPR, $sort->getValue($this->sphinx));
        $this->assertEquals("@weight + fulltext_field*200", $sortBy->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetSortMode_SPH_SORT_ATTR_DESC()
    {
        $instance = $this->sphinx->setSortMode(SPH_SORT_ATTR_DESC, 'year');

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $sort = $reflectionClass->getProperty('sort');
        $sort->setAccessible(true);
        $sortBy = $reflectionClass->getProperty('sortBy');
        $sortBy->setAccessible(true);

        $this->assertEquals(SPH_SORT_ATTR_DESC, $sort->getValue($this->sphinx));
        $this->assertEquals("year", $sortBy->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetSortMode_SPH_SORT_ATTR_ASC()
    {
        $instance = $this->sphinx->setSortMode(SPH_SORT_ATTR_ASC, 'year');

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $sort = $reflectionClass->getProperty('sort');
        $sort->setAccessible(true);
        $sortBy = $reflectionClass->getProperty('sortBy');
        $sortBy->setAccessible(true);

        $this->assertEquals(SPH_SORT_ATTR_ASC, $sort->getValue($this->sphinx));
        $this->assertEquals("year", $sortBy->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetSortMode_SPH_SORT_TIME_SEGMENTS()
    {
        $instance = $this->sphinx->setSortMode(SPH_SORT_TIME_SEGMENTS, 'year');

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $sort = $reflectionClass->getProperty('sort');
        $sort->setAccessible(true);
        $sortBy = $reflectionClass->getProperty('sortBy');
        $sortBy->setAccessible(true);

        $this->assertEquals(SPH_SORT_TIME_SEGMENTS, $sort->getValue($this->sphinx));
        $this->assertEquals("year", $sortBy->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetSortMode_SPH_SORT_EXTENDED()
    {
        $instance = $this->sphinx->setSortMode(SPH_SORT_EXTENDED, '@relevance DESC, year DESC, @id DESC');

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $sort = $reflectionClass->getProperty('sort');
        $sort->setAccessible(true);
        $sortBy = $reflectionClass->getProperty('sortBy');
        $sortBy->setAccessible(true);

        $this->assertEquals(SPH_SORT_EXTENDED, $sort->getValue($this->sphinx));
        $this->assertEquals("@relevance DESC, year DESC, @id DESC", $sortBy->getValue($this->sphinx));
    }

    /**
     *
     */
    public function testSetIDRangeErrorMinIsNotNumeric()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setIDRange(null, 10);
    }

    /**
     *
     */
    public function testSetIDRangeErrorMaxIsNotNumeric()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setIDRange(100, null);
    }

    /**
     *
     */
    public function testSetIDRangeErrorMinIsGreaterThanMax()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setIDRange(100, 10);
    }

    /**
     *
     */
    public function testSetIDRange()
    {
        $instance = $this->sphinx->setIDRange(100, 200);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $minId = $reflectionClass->getProperty('minId');
        $minId->setAccessible(true);
        $minId = $minId->getValue($this->sphinx);

        $maxId = $reflectionClass->getProperty('maxId');
        $maxId->setAccessible(true);
        $maxId = $maxId->getValue($this->sphinx);

        $this->assertEquals(100, $minId);
        $this->assertEquals(200, $maxId);
    }

    /**
     *
     */
    public function testSetFilterWithoutExcludeFlag()
    {
        $instance = $this->sphinx->setFilter('year', array(2014));

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $filters = $reflectionClass->getProperty('filters');
        $filters->setAccessible(true);
        $filters = $filters->getValue($this->sphinx);

        $this->assertInternalType('array', $filters[0]);
        $this->assertEquals('year', $filters[0]['attr']);
        $this->assertEquals(array(2014), $filters[0]['values']);
        $this->assertFalse($filters[0]['exclude']);
    }

    /**
     *
     */
    public function testSetFilterWithExcludeFlagTrue()
    {
        $instance = $this->sphinx->setFilter('year', array(2014), true);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $filters = $reflectionClass->getProperty('filters');
        $filters->setAccessible(true);
        $filters = $filters->getValue($this->sphinx);

        $this->assertInternalType('array', $filters[0]);
        $this->assertEquals('year', $filters[0]['attr']);
        $this->assertEquals(array(2014), $filters[0]['values']);
        $this->assertTrue($filters[0]['exclude']);
    }

    /**
     *
     */
    public function testSetFilterWithExcludeFlagOne()
    {
        $instance = $this->sphinx->setFilter('year', array(2014), 1);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $filters = $reflectionClass->getProperty('filters');
        $filters->setAccessible(true);
        $filters = $filters->getValue($this->sphinx);

        $this->assertInternalType('array', $filters[0]);
        $this->assertEquals('year', $filters[0]['attr']);
        $this->assertEquals(array(2014), $filters[0]['values']);
        $this->assertTrue($filters[0]['exclude']);
    }

    /**
     *
     */
    public function testSetFilterWithExcludeFlagZero()
    {
        $instance = $this->sphinx->setFilter('year', array(2014), 0);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $filters = $reflectionClass->getProperty('filters');
        $filters->setAccessible(true);
        $filters = $filters->getValue($this->sphinx);

        $this->assertInternalType('array', $filters[0]);
        $this->assertEquals('year', $filters[0]['attr']);
        $this->assertEquals(array(2014), $filters[0]['values']);
        $this->assertFalse($filters[0]['exclude']);
    }

    /**
     *
     */
    public function testSetFilterWithExcludeFlagBeingNonValidBooleanValue()
    {
        $instance = $this->sphinx->setFilter('year', array(2014), 'ThisShouldBeConvertedToFalse');

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $filters = $reflectionClass->getProperty('filters');
        $filters->setAccessible(true);
        $filters = $filters->getValue($this->sphinx);

        $this->assertInternalType('array', $filters[0]);
        $this->assertEquals('year', $filters[0]['attr']);
        $this->assertEquals(array(2014), $filters[0]['values']);
        $this->assertFalse($filters[0]['exclude']);
    }

    /**
     *
     */
    public function testSetFilterRangeErrorAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterRange(null, 2000, 2040);
    }

    /**
     *
     */
    public function testSetFilterRangeErrorMinIsNotNumeric()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterRange('year', null, 2040);
    }

    /**
     *
     */
    public function testSetFilterRangeErrorMaxIsNotNumeric()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterRange('year', 2000, null);
    }

    /**
     *
     */
    public function testSetFilterRangeErrorMinIsGreaterThanMax()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterRange('year', 2040, 2000);
    }

    /**
     *
     */
    public function testSetFilterRange()
    {
        $instance = $this->sphinx->setFilterRange('year', 2000, 2040);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $filters = $reflectionClass->getProperty('filters');
        $filters->setAccessible(true);
        $filters = $filters->getValue($this->sphinx);

        $this->assertInternalType('array', $filters[0]);
        $this->assertEquals('year', $filters[0]['attr']);
        $this->assertEquals(2000, $filters[0]['min']);
        $this->assertEquals(2040, $filters[0]['max']);
        $this->assertFalse($filters[0]['exclude']);
    }

    /**
     *
     */
    public function testSetFilterFloatRangeErrorAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterFloatRange(null, 6.5, 7.5);
    }

    /**
     *
     */
    public function testSetFilterFloatRangeErrorMinIsNotNumeric()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterFloatRange('float_attribute', null, 7.5);
    }

    /**
     *
     */
    public function testSetFilterFloatRangeErrorMaxIsNotNumeric()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterFloatRange('float_attribute', 6.5, null);
    }

    /**
     *
     */
    public function testSetFilterFloatRangeErrorMinIsGreaterThanMax()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterFloatRange('float_attribute', 7.5, 6.5);
    }

    /**
     *
     */
    public function testSetFilterFloatRange()
    {
        $instance = $this->sphinx->setFilterFloatRange('float_attribute', 6.5, 7.5);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $filters = $reflectionClass->getProperty('filters');
        $filters->setAccessible(true);
        $filters = $filters->getValue($this->sphinx);

        $this->assertInternalType('array', $filters[0]);
        $this->assertEquals('float_attribute', $filters[0]['attr']);
        $this->assertEquals(6.5, $filters[0]['min']);
        $this->assertEquals(7.5, $filters[0]['max']);
        $this->assertFalse($filters[0]['exclude']);
    }

    /**
     *
     */
    public function testSetGeoAnchorLatitudeAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGeoAnchor(null, 'lon_attr', 7.5, 6.5);
    }

    /**
     *
     */
    public function testSetGeoAnchorLongitudeAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGeoAnchor('lat_attr', null, 7.5, 6.5);
    }

    /**
     *
     */
    public function testSetGeoAnchorLatitudeValueIsNotFloat()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGeoAnchor('lat_attr', 'lon_attr', 7, 6.5);
    }

    /**
     *
     */
    public function testSetGeoAnchorLongitudeValueIsNotFloat()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGeoAnchor('lat_attr', 'lon_attr', 7.5, 6);
    }

    /**
     *
     */
    public function testSetGeoAnchor()
    {
        $instance = $this->sphinx->setGeoAnchor('lat_attr', 'lon_attr', 7.5, 6.5);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $anchor = $reflectionClass->getProperty('anchor');
        $anchor->setAccessible(true);
        $anchor = $anchor->getValue($this->sphinx);

        $this->assertInternalType('array', $anchor);
        $this->assertEquals(6.5, $anchor['long']);
        $this->assertEquals(7.5, $anchor['lat']);
        $this->assertEquals('lat_attr', $anchor['attrlat']);
        $this->assertEquals('lon_attr', $anchor['attrlong']);
    }

    /**
     *
     */
    public function testSetGroupByErrorAttributeNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGroupBy(null, SPH_GROUPBY_DAY);
    }

    /**
     *
     */
    public function testSetGroupByErrorGroupSortNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGroupBy('year', SPH_GROUPBY_DAY, null);
    }

    /**
     *
     */
    public function testSetGroupByErrorGroupByNotValid()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGroupBy('year', 1000000000);
    }

    /**
     *
     */
    public function testSetGroupBy_SPH_GROUPBY_DAY()
    {
        $instance = $this->sphinx->setGroupBy('year', SPH_GROUPBY_DAY);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $groupBy = $reflectionClass->getProperty('groupBy');
        $groupBy->setAccessible(true);
        $groupBy = $groupBy->getValue($this->sphinx);

        $groupFunc = $reflectionClass->getProperty('groupFunc');
        $groupFunc->setAccessible(true);
        $groupFunc = $groupFunc->getValue($this->sphinx);

        $groupSort = $reflectionClass->getProperty('groupSort');
        $groupSort->setAccessible(true);
        $groupSort = $groupSort->getValue($this->sphinx);

        $this->assertEquals('year', $groupBy);
        $this->assertEquals(SPH_GROUPBY_DAY, $groupFunc);
        $this->assertEquals("@group desc", $groupSort);
    }

    /**
     *
     */
    public function testSetGroupBy_SPH_GROUPBY_WEEK()
    {
        $instance = $this->sphinx->setGroupBy('year', SPH_GROUPBY_WEEK);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $groupBy = $reflectionClass->getProperty('groupBy');
        $groupBy->setAccessible(true);
        $groupBy = $groupBy->getValue($this->sphinx);

        $groupFunc = $reflectionClass->getProperty('groupFunc');
        $groupFunc->setAccessible(true);
        $groupFunc = $groupFunc->getValue($this->sphinx);

        $groupSort = $reflectionClass->getProperty('groupSort');
        $groupSort->setAccessible(true);
        $groupSort = $groupSort->getValue($this->sphinx);

        $this->assertEquals('year', $groupBy);
        $this->assertEquals(SPH_GROUPBY_WEEK, $groupFunc);
        $this->assertEquals("@group desc", $groupSort);
    }

    /**
     *
     */
    public function testSetGroupBy_SPH_GROUPBY_MONTH()
    {
        $instance = $this->sphinx->setGroupBy('year', SPH_GROUPBY_MONTH);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $groupBy = $reflectionClass->getProperty('groupBy');
        $groupBy->setAccessible(true);
        $groupBy = $groupBy->getValue($this->sphinx);

        $groupFunc = $reflectionClass->getProperty('groupFunc');
        $groupFunc->setAccessible(true);
        $groupFunc = $groupFunc->getValue($this->sphinx);

        $groupSort = $reflectionClass->getProperty('groupSort');
        $groupSort->setAccessible(true);
        $groupSort = $groupSort->getValue($this->sphinx);

        $this->assertEquals('year', $groupBy);
        $this->assertEquals(SPH_GROUPBY_MONTH, $groupFunc);
        $this->assertEquals("@group desc", $groupSort);
    }

    /**
     *
     */
    public function testSetGroupBy_SPH_GROUPBY_YEAR()
    {
        $instance = $this->sphinx->setGroupBy('year', SPH_GROUPBY_YEAR);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $groupBy = $reflectionClass->getProperty('groupBy');
        $groupBy->setAccessible(true);
        $groupBy = $groupBy->getValue($this->sphinx);

        $groupFunc = $reflectionClass->getProperty('groupFunc');
        $groupFunc->setAccessible(true);
        $groupFunc = $groupFunc->getValue($this->sphinx);

        $groupSort = $reflectionClass->getProperty('groupSort');
        $groupSort->setAccessible(true);
        $groupSort = $groupSort->getValue($this->sphinx);

        $this->assertEquals('year', $groupBy);
        $this->assertEquals(SPH_GROUPBY_YEAR, $groupFunc);
        $this->assertEquals("@group desc", $groupSort);
    }

    /**
     *
     */
    public function testSetGroupBy_SPH_GROUPBY_ATTR()
    {
        $instance = $this->sphinx->setGroupBy('year', SPH_GROUPBY_ATTR);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $groupBy = $reflectionClass->getProperty('groupBy');
        $groupBy->setAccessible(true);
        $groupBy = $groupBy->getValue($this->sphinx);

        $groupFunc = $reflectionClass->getProperty('groupFunc');
        $groupFunc->setAccessible(true);
        $groupFunc = $groupFunc->getValue($this->sphinx);

        $groupSort = $reflectionClass->getProperty('groupSort');
        $groupSort->setAccessible(true);
        $groupSort = $groupSort->getValue($this->sphinx);

        $this->assertEquals('year', $groupBy);
        $this->assertEquals(SPH_GROUPBY_ATTR, $groupFunc);
        $this->assertEquals("@group desc", $groupSort);
    }

    /**
     *
     */
    public function testSetGroupBy_SPH_GROUPBY_ATTRPAIR()
    {
        $instance = $this->sphinx->setGroupBy('year', SPH_GROUPBY_ATTRPAIR);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $groupBy = $reflectionClass->getProperty('groupBy');
        $groupBy->setAccessible(true);
        $groupBy = $groupBy->getValue($this->sphinx);

        $groupFunc = $reflectionClass->getProperty('groupFunc');
        $groupFunc->setAccessible(true);
        $groupFunc = $groupFunc->getValue($this->sphinx);

        $groupSort = $reflectionClass->getProperty('groupSort');
        $groupSort->setAccessible(true);
        $groupSort = $groupSort->getValue($this->sphinx);

        $this->assertEquals('year', $groupBy);
        $this->assertEquals(SPH_GROUPBY_ATTRPAIR, $groupFunc);
        $this->assertEquals("@group desc", $groupSort);
    }

    /**
     *
     */
    public function testSetGroupDistinctAttributeNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGroupDistinct(1);
    }

    /**
     *
     */
    public function testSetGroupDistinct()
    {
        $instance = $this->sphinx->setGroupDistinct('year');

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $groupDistinct = $reflectionClass->getProperty('groupDistinct');
        $groupDistinct->setAccessible(true);
        $groupDistinct = $groupDistinct->getValue($this->sphinx);

        $this->assertEquals('year', $groupDistinct);
    }

    /**
     *
     */
    public function testSetRetriesErrorCountIsNotInteger()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setRetries('A');
    }

    /**
     *
     */
    public function testSetRetriesErrorDelayIsNotInteger()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setRetries(2, 'A');
    }

    /**
     *
     */
    public function testSetRetriesErrorCountIsNegativeInteger()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setRetries(-2);
    }

    /**
     *
     */
    public function testSetRetriesErrorDelayIsNegativeInteger()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setRetries(2, -2);
    }

    /**
     *
     */
    public function testSetRetries()
    {
        $instance = $this->sphinx->setRetries(5, 1);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $retryCount = $reflectionClass->getProperty('retryCount');
        $retryCount->setAccessible(true);
        $retryCount = $retryCount->getValue($this->sphinx);

        $retryDelay = $reflectionClass->getProperty('retryDelay');
        $retryDelay->setAccessible(true);
        $retryDelay = $retryDelay->getValue($this->sphinx);

        $this->assertEquals(5, $retryCount);
        $this->assertEquals(1, $retryDelay);
    }

    /**
     *
     */
    public function testSetArrayResult()
    {
        $instance = $this->sphinx->setArrayResult(true);

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $arrayResult = $reflectionClass->getProperty('arrayResult');
        $arrayResult->setAccessible(true);
        $arrayResult = $arrayResult->getValue($this->sphinx);

        $this->assertTrue($arrayResult);
    }

    /**
     *
     */
    public function testSetArrayResultErrorParamIsNotBoolean()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setArrayResult(2);
    }

    /**
     *
     */
    public function testSetOverrideErrorAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setOverride(null, SPH_ATTR_INTEGER, array(2004, 2005, 2006, 2007));
    }

    /**
     *
     */
    public function testSetOverrideErrorAttributeTypeIsNotValid()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setOverride('year', 100000, array(2004, 2005, 2006, 2007));
    }

    /**
     *
     */
    public function testSetOverrideErrorAttributeIsNotArray()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setOverride('year', SPH_ATTR_INTEGER, 2004);
    }

    /**
     *
     */
    public function testSetOverride()
    {
        $instance = $this->sphinx->setOverride('year', SPH_ATTR_INTEGER, array(2004, 2005, 2006, 2007));

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $overrides = $reflectionClass->getProperty('overrides');
        $overrides->setAccessible(true);
        $overrides = $overrides->getValue($this->sphinx);

        $this->assertArrayHasKey('year', $overrides);
        $this->assertEquals('year', $overrides['year']['attr']);
        $this->assertEquals(SPH_ATTR_INTEGER, $overrides['year']['type']);
        $this->assertEquals(array(2004, 2005, 2006, 2007), $overrides['year']['values']);
    }

    /**
     *
     */
    public function testSetSelectErrorIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setSelect(null);
    }

    /**
     *
     */
    public function testSetSelect()
    {
        $instance = $this->sphinx->setSelect("*, @weight+(user_karma+ln(pageviews))*0.1 AS myweight");

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $select = $reflectionClass->getProperty('select');
        $select->setAccessible(true);
        $select = $select->getValue($this->sphinx);

        $this->assertEquals("*, @weight+(user_karma+ln(pageviews))*0.1 AS myweight", $select);
    }

    /**
     *
     */
    public function testResetFilters()
    {
        $instance = $this->sphinx->resetFilters();

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $filters = $reflectionClass->getProperty('filters');
        $filters->setAccessible(true);
        $filters = $filters->getValue($this->sphinx);

        $anchor = $reflectionClass->getProperty('anchor');
        $anchor->setAccessible(true);
        $anchor = $anchor->getValue($this->sphinx);

        $this->assertEquals(array(), $filters);
        $this->assertEquals(array(), $anchor);
    }

    /**
     *
     */
    public function testResetGroupBy()
    {
        $instance = $this->sphinx->resetGroupBy();

        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $groupBy = $reflectionClass->getProperty('groupBy');
        $groupBy->setAccessible(true);
        $groupBy = $groupBy->getValue($this->sphinx);

        $groupFunc = $reflectionClass->getProperty('groupFunc');
        $groupFunc->setAccessible(true);
        $groupFunc = $groupFunc->getValue($this->sphinx);

        $groupSort = $reflectionClass->getProperty('groupSort');
        $groupSort->setAccessible(true);
        $groupSort = $groupSort->getValue($this->sphinx);

        $groupDistinct = $reflectionClass->getProperty('groupDistinct');
        $groupDistinct->setAccessible(true);
        $groupDistinct = $groupDistinct->getValue($this->sphinx);

        $this->assertEquals("", $groupBy);
        $this->assertEquals(SPH_GROUPBY_DAY, $groupFunc);
        $this->assertEquals("@group desc", $groupSort);
        $this->assertEquals("", $groupDistinct);
    }

    /**
     *
     */
    public function testResetOverrides()
    {
        $instance = $this->sphinx->resetOverrides();
        $this->assertInstanceOf($this->instanceName, $instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $overrides = $reflectionClass->getProperty('overrides');
        $overrides->setAccessible(true);
        $overrides = $overrides->getValue($this->sphinx);

        $this->assertEquals(array(), $overrides);
    }

    /**
     *
     */
    public function testAddQuery()
    {
        $actual = $this->sphinx->addQuery("some search terms 1");
        $this->assertEquals(0, $actual);

        $actual = $this->sphinx->addQuery("some search terms 2");
        $this->assertEquals(1, $actual);

        $actual = $this->sphinx->addQuery("some search terms 3");
        $this->assertEquals(2, $actual);
    }

    /**
     *
     */
    public function testAddQueryWithIndex()
    {
        $actual = $this->sphinx->addQuery("some search terms 1", 'movies1');
        $this->assertEquals(0, $actual);

        $actual = $this->sphinx->addQuery("some search terms 2", 'movies2');
        $this->assertEquals(1, $actual);

        $actual = $this->sphinx->addQuery("some search terms 3", 'movies3');
        $this->assertEquals(2, $actual);

    }

    /**
     *
     */
    public function testAddQueryWithIndexAndComment()
    {
        $actual = $this->sphinx->addQuery("some search terms1", 'movies1', 'This query fetches movie titles.');
        $this->assertEquals(0, $actual);

        $actual = $this->sphinx->addQuery("some search terms2", 'movies2', 'This query fetches movie titles.');
        $this->assertEquals(1, $actual);

        $actual = $this->sphinx->addQuery("some search terms3", 'movies3', 'This query fetches movie titles.');
        $this->assertEquals(2, $actual);
    }

    /**
     *
     */
    public function testRunQueries()
    {
        if ($this->sphinx->isConnectError() === false) {
            $this->sphinx = new SphinxClient();
            $this->sphinx->setServer(SPHINX_HOST, SPHINX_PORT);
            $this->sphinx->addQuery('Spider-Man', 'movies');

            $actual = $this->sphinx->runQueries();
            $this->assertInternalType('array', $actual);
        } else {
            $this->markTestSkipped();
        }
    }

    /**
     *
     */
    public function testRunQueriesError()
    {
        $this->sphinx = new SphinxClient();
        $this->sphinx->setServer(SPHINX_HOST, 6666);
        $this->sphinx->addQuery('Spider-Man', 'movies');

        $actual = $this->sphinx->runQueries();
        $this->assertFalse($actual);
    }

    /**
     *
     */
    public function testBuildExcerptsErrorDocsIsNotArray()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->buildExcerpts(null, 'index', 'some keywords', array());
    }

    /**
     *
     */
    public function testBuildExcerptsErrorIndexIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->buildExcerpts(array(), null, 'some keywords', array());
    }

    /**
     *
     */
    public function testBuildExcerptsErrorWordsIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->buildExcerpts(array(), 'index', null, array());
    }

    /**
     *
     */
    public function testBuildExcerptsErrorOptionsIsNotArray()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->buildExcerpts(array(), 'index', 'some keywords', null);
    }

    /**
     * Needs a valid Sphinx.conf loaded with the provided movies.sql to indexer to be tested properly.
     */
    public function testBuildExcerptsFailsOnSearchd()
    {
        $this->sphinx->close();
        $docs = array
        (
            'Spider-Man is a fictional character, a comic book superhero who appears in comic books published by Marvel Comics.',
            'he Spider-Man series broke ground by featuring Peter Parker, a teenage high school student and person behind Spider-Man\'s secret identity to whose "self-obsessions with rejection, inadequacy, and loneliness" young readers could relate',
            'Marvel has featured Spider-Man in several comic book series, the first and longest-lasting of which is titled The Amazing Spider-Man. Over the years, the Peter Parker character has developed from shy, nerdy high school student to troubled but outgoing college student, to married high school teacher to, in the late 2000s, a single freelance photographer, his most typical adult role'
        );
        $index = 'movies';
        $words = 'Spider-Man';

        $this->sphinx
            ->setServer('2013.192.168.0.1')
            ->query($words);

        $actual = $this->sphinx->buildExcerpts($docs, $index, $words);
        $this->assertFalse($actual);
    }

    /**
     * Needs a valid Sphinx.conf loaded with the provided movies.sql to indexer to be tested properly.
     */
    public function testBuildExcerptsResults()
    {
        if ($this->sphinx->isConnectError() === false) {
            $docs = array
            (
                'Spider-Man is a fictional character, a comic book superhero who appears in comic books published by Marvel Comics.',
                'The Spider-Man series broke ground by featuring Peter Parker, a teenage high school student and person behind Spider-Man\'s secret identity to whose "self-obsessions with rejection, inadequacy, and loneliness" young readers could relate',
                'Marvel has featured Spider-Man in several comic book series, the first and longest-lasting of which is titled The Amazing Spider-Man. Over the years, the Peter Parker character has developed from shy, nerdy high school student to troubled but outgoing college student, to married high school teacher to, in the late 2000s, a single freelance photographer, his most typical adult role'
            );
            $words = 'Spider-Man';

            $actual = $this->sphinx->buildExcerpts($docs, 'movies', $words);

            $this->assertInternalType('array', $actual);
            $this->assertNotEmpty($actual);
            $this->assertCount(3, $docs);
        } else {
            $this->markTestSkipped('testBuildExcerptsResults was skipped.');
        }
    }

    /**
     *
     */
    public function testEscapeStringAtSymbol()
    {
        $actual = $this->sphinx->escapeString('@groupby \\at~ and & (symbols) mu-st "be" es/ca=ped ^__^$ ');
        $this->assertEquals('\@groupby \\\\at\~ and \& \(symbols\) mu\-st \"be\" es\/ca\=ped \^__\^\$ ', $actual);
    }

    /**
     * Needs a valid Sphinx.conf loaded with the provided movies.sql to indexer to be tested properly.
     */
    public function testBuildKeywordsWithExistingKeyWord()
    {
        if ($this->sphinx->isConnectError() === false) {

            $query = 'Spider-Man';
            $hits = true;
            $actual = $this->sphinx->buildKeywords($query, 'movies', $hits);

            $this->assertInternalType('array', $actual);
            $this->assertNotEmpty($actual);
            $this->assertEquals(6, $actual[0]['docs']);
        } else {
            $this->markTestSkipped('testBuildKeywordsWithExistingKeyWord was skipped.');
        }
    }

    /**
     * Needs a valid Sphinx.conf loaded with the provided movies.sql to indexer to be tested properly.
     */
    public function testBuildKeywordsWithNonExistentKeyWord()
    {
        if ($this->sphinx->isConnectError() === false) {
            $query = 'Batman';
            $hits = true;
            $actual = $this->sphinx->buildKeywords($query, 'movies', $hits);

            $this->assertInternalType('array', $actual);
            $this->assertNotEmpty($actual);
            $this->assertEquals(0, $actual[0]['docs']);
        } else {
            $this->markTestSkipped('testBuildKeywordsWithNonExistentKeyWord was skipped.');
        }
    }

    /**
     *
     */
    public function testUpdateAttributesError()
    {
        $this->sphinx = new SphinxClient();

        //Update year value from 2020 to 2040.
        $actual = $this->sphinx->updateAttributes('movies', array('year'), array(2020 => array(2040)));
        $this->assertEquals(-1, $actual);
    }

    /**
     * Needs a valid Sphinx.conf loaded with the provided movies.sql to indexer to be tested properly.
     */
    public function testUpdateAttributes()
    {
        if ($this->sphinx->isConnectError() === false) {
            $actual = $this->sphinx->updateAttributes('movies', array('year'), array(2020 => array(2040)));
            $this->assertNotEquals(-1, $actual);
        } else {
            $this->markTestSkipped('testUpdateAttributes was skipped.');
        }
    }

    /**
     * Needs a valid Sphinx.conf loaded with the provided movies.sql to indexer to be tested properly.
     */
    public function testCloseWhenConnectionNotEstablished()
    {
        $this->sphinx = new SphinxClient();
        $this->sphinx->close();

        $this->assertFalse($this->sphinx->close());
    }

    /**
     * Needs a valid Sphinx.conf loaded with the provided movies.sql to indexer to be tested properly.
     */
    public function testFlushAttributes()
    {
        if ($this->sphinx->isConnectError() === false) {
            $actual = $this->sphinx->flushAttributes();
            $this->assertNotEquals(-1, $actual);
        } else {
            $this->markTestSkipped('testFlushAttributes was skipped.');
        }
    }

    /**
     *
     */
    public function testFlushAttributesError()
    {
        $this->sphinx = new SphinxClient();
        $actual = $this->sphinx->flushAttributes();

        $this->assertEquals(-1, $actual);
        $this->assertNotEmpty($this->sphinx->getLastError());
    }

    /**
     * Needs a valid Sphinx.conf loaded with the provided movies.sql to indexer to be tested properly.
     */
    public function testCloseWhenConnectionEstablishedWithWrongData()
    {
        $this->sphinx->close();
        $this->sphinx
            ->setServer('2013.192.168.0.1')
            ->query('test');

        $actual = $this->sphinx->status();
        $this->assertInternalType('boolean', $actual);
        $this->assertFalse($actual);
    }

    /**
     *
     */
    public function tearDown()
    {
        $this->sphinx->close();
        $this->sphinx = null;
    }
}
