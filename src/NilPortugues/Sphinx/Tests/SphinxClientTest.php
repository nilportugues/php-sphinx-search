<?php

/**
 * Testing the SphinxClient class.
 * This does not test the search results returned by searchd.
 */
class SphinxClientTest extends \PHPUnit_Framework_TestCase
{
    protected $sphinx;
    protected $instanceName;

    public function setUp()
    {
            $this->instanceName = '\NilPortugues\Sphinx\SphinxClient';

            $this->sphinx = new \NilPortugues\Sphinx\SphinxClient();
            $this->sphinx
                ->setServer(SPHINX_HOST,SPHINX_PORT);
    }

    public function testRemoveFilter()
    {
        $this->sphinx->setFilter('year',array(2014));
        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_filters = $reflectionClass->getProperty('_filters');
        $_filters->setAccessible(true);
        $_filters = $_filters->getValue($this->sphinx);
        $_filters = $_filters[0];

        $this->assertInternalType('array',$_filters);
        $this->assertEquals('year',$_filters['attr']);
        $this->assertEquals(array(2014),$_filters['values']);

        $instance = $this->sphinx->removeFilter('year');

        $this->assertInstanceOf($this->instanceName,$instance);

        $_filters = $reflectionClass->getProperty('_filters');
        $_filters->setAccessible(true);
        $_filters = $_filters->getValue($this->sphinx);

        $this->assertInternalType('array',$_filters);
        $this->assertEquals(array(),$_filters);
    }

    public function testGetLastError()
    {
        $this->assertEquals('',$this->sphinx->getLastError());
    }

    public function testGetLastErrorWhenConnectingToNonExistentHost()
    {
        $this->sphinx
            ->setServer('2013.192.168.0.1')
            ->query('test')
        ;

        $this->assertEquals
        (
            'connection to 2013.192.168.0.1:9312 failed (errno=0, msg=php_network_getaddresses: getaddrinfo failed: Name or service not known)',
            $this->sphinx->getLastError()
        );
    }

    public function testGetLastErrorWhenConnectionNotEstablished()
    {

    }

    public function testGetLastWarning()
    {

    }

    public function testIsConnectErrorNoConnectionInitialized()
    {
        $this->sphinx = new \NilPortugues\Sphinx\SphinxClient();
        $actual = $this->sphinx->isConnectError();
        $this->assertFalse($actual);
    }

    public function testIsConnectErrorWhenConnectionInitializedWithWrongData()
    {
        $this->sphinx
            ->setServer('0.0.0.1',SPHINX_PORT)
            ->query('test')
        ;
        $actual = $this->sphinx->isConnectError();
        $this->assertTrue($actual);
    }

    public function testSetServerHostAndPort()
    {
        $instance = $this->sphinx->setServer(SPHINX_HOST,80);
        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_path = $reflectionClass->getProperty('_path');
        $_path->setAccessible(true);

        $_port = $reflectionClass->getProperty('_port');
        $_port->setAccessible(true);

        $this->assertEquals( '', $_path->getValue($this->sphinx) );
        $this->assertEquals( 80, $_port->getValue($this->sphinx) );
    }

    public function testSetServerHostOnly()
    {
        $instance = $this->sphinx->setServer(SPHINX_HOST);
        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_path = $reflectionClass->getProperty('_path');
        $_path->setAccessible(true);

        $_port = $reflectionClass->getProperty('_port');
        $_port->setAccessible(true);

        $this->assertEquals( '', $_path->getValue($this->sphinx) );
        $this->assertEquals( 9312, $_port->getValue($this->sphinx) );
    }

    public function testSetConnectTimeout()
    {
        $instance = $this->sphinx->setConnectTimeout( 10 );
        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_timeout = $reflectionClass->getProperty('_timeout');
        $_timeout->setAccessible(true);
        $this->assertEquals(10,$_timeout->getValue($this->sphinx));
    }

    public function testSetConnectTimeoutErrorOffsetBelowZero()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setConnectTimeout( -10 );
    }

    public function testSetLimits()
    {
        $instance = $this->sphinx->setLimits( 10, 100, 1000, 500 );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_offset = $reflectionClass->getProperty('_offset');
        $_offset->setAccessible(true);

        $_limit = $reflectionClass->getProperty('_limit');
        $_limit->setAccessible(true);

        $_maxmatches = $reflectionClass->getProperty('_maxmatches');
        $_maxmatches->setAccessible(true);

        $_cutoff = $reflectionClass->getProperty('_cutoff');
        $_cutoff->setAccessible(true);

        $this->assertEquals(10,$_offset->getValue($this->sphinx));
        $this->assertEquals(100,$_limit->getValue($this->sphinx));
        $this->assertEquals(1000,$_maxmatches->getValue($this->sphinx));
        $this->assertEquals(500,$_cutoff->getValue($this->sphinx));
    }

    public function testSetLimitsErrorOffsetBelowZero()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setLimits( -10, 100, 1000, 500 );
    }

    public function testSetLimitsErrorLimitBelowZero()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setLimits( 10, -100, 1000, 500 );
    }

    public function testSetLimitsErrorMaxMatchesBelowZero()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setLimits( 10, 100, -1000, 500 );
    }

    public function testSetLimitsErrorCutOffBelowZero()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setLimits( 10, 100, 1000, -500 );
    }


    public function testSetMaxQueryTime()
    {
        $instance = $this->sphinx->setMaxQueryTime( 10 );
        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_maxquerytime = $reflectionClass->getProperty('_maxquerytime');
        $_maxquerytime->setAccessible(true);

        $this->assertEquals(10,$_maxquerytime->getValue($this->sphinx));
    }

    public function testSetMaxQueryErrorTimeBelowZero()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setMaxQueryTime( -10 );
    }

    public function testSetMaxQueryErrorIsNotInteger()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setMaxQueryTime( NULL );
    }

    public function testSetMatchModeErrorValueIsNotValid()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setMatchMode( 100000000 );
    }

    public function testSetMatchMode_SPH_MATCH_ALL()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_ALL);
        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_ALL,$_mode->getValue($this->sphinx));
    }

    public function testSetMatchMode_SPH_MATCH_ANY()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_ANY);
        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_ANY,$_mode->getValue($this->sphinx));
    }

    public function testSetMatchMode_SPH_MATCH_PHRASE()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_PHRASE);
        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_PHRASE,$_mode->getValue($this->sphinx));
    }

    public function testSetMatchMode_SPH_MATCH_BOOLEAN()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_BOOLEAN);
        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_BOOLEAN,$_mode->getValue($this->sphinx));
    }

    public function testSetMatchMode_SPH_MATCH_EXTENDED()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_EXTENDED);
        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_EXTENDED,$_mode->getValue($this->sphinx));
    }

    public function testSetMatchMode_SPH_MATCH_FULLSCAN()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_FULLSCAN);
        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_FULLSCAN,$_mode->getValue($this->sphinx));
    }

    public function testSetMatchMode_SPH_MATCH_EXTENDED2()
    {
        $instance = $this->sphinx->setMatchMode(SPH_MATCH_EXTENDED2);
        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_EXTENDED2,$_mode->getValue($this->sphinx));
    }

    public function testSetRankingMode_SPH_RANK_EXPR()
    {
        $instance = $this->sphinx->setRankingMode( SPH_RANK_EXPR );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_EXPR,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_FIELDMASK()
    {
        $instance = $this->sphinx->setRankingMode( SPH_RANK_FIELDMASK );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_FIELDMASK,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_MATCHANY()
    {
        $instance = $this->sphinx->setRankingMode( SPH_RANK_MATCHANY );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_MATCHANY,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_WORDCOUNT()
    {
        $instance = $this->sphinx->setRankingMode( SPH_RANK_WORDCOUNT );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_WORDCOUNT,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_PROXIMITY()
    {
        $instance = $this->sphinx->setRankingMode( SPH_RANK_PROXIMITY );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_PROXIMITY,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_PROXIMITY_BM25()
    {
        $instance = $this->sphinx->setRankingMode( SPH_RANK_PROXIMITY_BM25 );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_PROXIMITY_BM25,$_ranker->getValue($this->sphinx));

    }


    public function testSetRankingMode_SPH_RANK_BM25()
    {
        $instance = $this->sphinx->setRankingMode( SPH_RANK_BM25 );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_BM25,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_NONE()
    {
        $instance = $this->sphinx->setRankingMode( SPH_RANK_NONE );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_NONE,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_SPH04()
    {
        $instance = $this->sphinx->setRankingMode( SPH_RANK_SPH04, "Some valid rank expression" );

        $this->assertInstanceOf($this->instanceName,$instance);
        
        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_SPH04,$_ranker->getValue($this->sphinx));

        $_rankexpr = $reflectionClass->getProperty('_rankexpr');
        $_rankexpr->setAccessible(true);
        $this->assertEquals("Some valid rank expression",$_rankexpr->getValue($this->sphinx));

    }

    public function testSetRankingModeErrorValueIsNotValid()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $instance = $this->sphinx->setRankingMode( 100000000 );
    }

    public function testSetRankingModeErrorAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $instance = $this->sphinx->setRankingMode( SPH_RANK_SPH04, NULL );
    }

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

    public function testSetFieldWeights()
    {
        $weights = array
        (
            'index_attribute1' => 10,
            'index_attribute2' => 100,
        );
        $instance = $this->sphinx->SetFieldWeights($weights);

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_fieldweights = $reflectionClass->getProperty('_fieldweights');
        $_fieldweights->setAccessible(true);

        $this->assertEquals($weights,$_fieldweights->getValue($this->sphinx));
    }

    public function testSetIndexWeights()
    {
        $weights = array
        (
            'fulltext_attribute1' => 10,
            'fulltext_attribute2' => 100,
        );
        $instance = $this->sphinx->SetIndexWeights($weights);

        $this->assertInstanceOf($this->instanceName,$instance);
        
        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_indexweights = $reflectionClass->getProperty('_indexweights');
        $_indexweights->setAccessible(true);

        $this->assertEquals($weights,$_indexweights->getValue($this->sphinx));
    }

    public function testSetSortModeErrorValueIsNotValid()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setSortMode( 100000000 );
    }

    public function testSetSortModeErrorAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setSortMode( SPH_SORT_ATTR_DESC, NULL );
    }

    public function testSetSortModeErrorModeIsTextStringDefaultsTo_SPH_SORT_RELEVANCE()
    {
        $instance = $this->sphinx->setSortMode( "SPH_SORT_ATTR_DESC" );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_sort = $reflectionClass->getProperty('_sort');
        $_sort->setAccessible(true);
        $_sortby = $reflectionClass->getProperty('_sortby');
        $_sortby->setAccessible(true);

        $this->assertNotEquals(SPH_SORT_ATTR_DESC ,$_sort->getValue($this->sphinx));
        $this->assertEquals(SPH_SORT_RELEVANCE ,$_sort->getValue($this->sphinx));
        $this->assertEquals("",$_sortby->getValue($this->sphinx));
    }

    public function testSetSortMode_SPH_SORT_RELEVANCE()
    {
        $instance = $this->sphinx->setSortMode( SPH_SORT_RELEVANCE );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_sort = $reflectionClass->getProperty('_sort');
        $_sort->setAccessible(true);
        $_sortby = $reflectionClass->getProperty('_sortby');
        $_sortby->setAccessible(true);

        $this->assertEquals(SPH_SORT_RELEVANCE ,$_sort->getValue($this->sphinx));
        $this->assertEquals("",$_sortby->getValue($this->sphinx));
    }

    public function testSetSortMode_SPH_SORT_EXPR()
    {
        $instance = $this->sphinx->setSortMode(SPH_SORT_EXPR, "@weight + fulltext_field*200");

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_sort = $reflectionClass->getProperty('_sort');
        $_sort->setAccessible(true);
        $_sortby = $reflectionClass->getProperty('_sortby');
        $_sortby->setAccessible(true);

        $this->assertEquals(SPH_SORT_EXPR,$_sort->getValue($this->sphinx));
        $this->assertEquals("@weight + fulltext_field*200",$_sortby->getValue($this->sphinx));
    }

    public function testSetSortMode_SPH_SORT_ATTR_DESC()
    {
        $instance = $this->sphinx->setSortMode(SPH_SORT_ATTR_DESC,'year');

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_sort = $reflectionClass->getProperty('_sort');
        $_sort->setAccessible(true);
        $_sortby = $reflectionClass->getProperty('_sortby');
        $_sortby->setAccessible(true);

        $this->assertEquals(SPH_SORT_ATTR_DESC,$_sort->getValue($this->sphinx));
        $this->assertEquals("year",$_sortby->getValue($this->sphinx));
    }

    public function testSetSortMode_SPH_SORT_ATTR_ASC()
    {
        $instance = $this->sphinx->setSortMode(SPH_SORT_ATTR_ASC,'year');

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_sort = $reflectionClass->getProperty('_sort');
        $_sort->setAccessible(true);
        $_sortby = $reflectionClass->getProperty('_sortby');
        $_sortby->setAccessible(true);

        $this->assertEquals(SPH_SORT_ATTR_ASC,$_sort->getValue($this->sphinx));
        $this->assertEquals("year",$_sortby->getValue($this->sphinx));
    }

    public function testSetSortMode_SPH_SORT_TIME_SEGMENTS()
    {
        $instance = $this->sphinx->setSortMode(SPH_SORT_TIME_SEGMENTS,'year');

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_sort = $reflectionClass->getProperty('_sort');
        $_sort->setAccessible(true);
        $_sortby = $reflectionClass->getProperty('_sortby');
        $_sortby->setAccessible(true);

        $this->assertEquals(SPH_SORT_TIME_SEGMENTS,$_sort->getValue($this->sphinx));
        $this->assertEquals("year",$_sortby->getValue($this->sphinx));
    }

    public function testSetSortMode_SPH_SORT_EXTENDED()
    {
        $instance = $this->sphinx->setSortMode(SPH_SORT_EXTENDED,'@relevance DESC, year DESC, @id DESC');

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_sort = $reflectionClass->getProperty('_sort');
        $_sort->setAccessible(true);
        $_sortby = $reflectionClass->getProperty('_sortby');
        $_sortby->setAccessible(true);

        $this->assertEquals(SPH_SORT_EXTENDED,$_sort->getValue($this->sphinx));
        $this->assertEquals("@relevance DESC, year DESC, @id DESC",$_sortby->getValue($this->sphinx));
    }

    public function testSetIDRangeErrorMinIsNotNumeric()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setIDRange ( NULL, 10 );
    }

    public function testSetIDRangeErrorMaxIsNotNumeric()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setIDRange ( 100, NULL );
    }

    public function testSetIDRangeErrorMinIsGreaterThanMax()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setIDRange ( 100, 10 );
    }

    public function testSetIDRange()
    {
        $instance = $this->sphinx->setIDRange( 100, 200 );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_min_id = $reflectionClass->getProperty('_min_id');
        $_min_id->setAccessible(true);
        $_min_id = $_min_id->getValue($this->sphinx);

        $_max_id = $reflectionClass->getProperty('_max_id');
        $_max_id->setAccessible(true);
        $_max_id = $_max_id->getValue($this->sphinx);

        $this->assertEquals(100,$_min_id);
        $this->assertEquals(200,$_max_id);
    }

    public function testSetFilterWithoutExcludeFlag()
    {
        $instance = $this->sphinx->setFilter('year',array(2014));

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_filters = $reflectionClass->getProperty('_filters');
        $_filters->setAccessible(true);
        $_filters = $_filters->getValue($this->sphinx);

        $this->assertInternalType('array',$_filters[0]);
        $this->assertEquals('year',$_filters[0]['attr']);
        $this->assertEquals(array(2014),$_filters[0]['values']);
        $this->assertFalse($_filters[0]['exclude']);
    }

    public function testSetFilterWithExcludeFlagTrue()
    {
        $instance = $this->sphinx->setFilter('year',array(2014),true);

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_filters = $reflectionClass->getProperty('_filters');
        $_filters->setAccessible(true);
        $_filters = $_filters->getValue($this->sphinx);

        $this->assertInternalType('array',$_filters[0]);
        $this->assertEquals('year',$_filters[0]['attr']);
        $this->assertEquals(array(2014),$_filters[0]['values']);
        $this->assertTrue($_filters[0]['exclude']);
    }

    public function testSetFilterWithExcludeFlagOne()
    {
        $instance = $this->sphinx->setFilter('year',array(2014),1);

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_filters = $reflectionClass->getProperty('_filters');
        $_filters->setAccessible(true);
        $_filters = $_filters->getValue($this->sphinx);

        $this->assertInternalType('array',$_filters[0]);
        $this->assertEquals('year',$_filters[0]['attr']);
        $this->assertEquals(array(2014),$_filters[0]['values']);
        $this->assertTrue($_filters[0]['exclude']);
    }

    public function testSetFilterWithExcludeFlagZero()
    {
        $instance = $this->sphinx->setFilter('year',array(2014),0);

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_filters = $reflectionClass->getProperty('_filters');
        $_filters->setAccessible(true);
        $_filters = $_filters->getValue($this->sphinx);

        $this->assertInternalType('array',$_filters[0]);
        $this->assertEquals('year',$_filters[0]['attr']);
        $this->assertEquals(array(2014),$_filters[0]['values']);
        $this->assertFalse($_filters[0]['exclude']);
    }

    public function testSetFilterWithExcludeFlagBeingNonValidBooleanValue()
    {
        $instance = $this->sphinx->setFilter('year',array(2014),'ThisShouldBeConvertedToFalse');

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_filters = $reflectionClass->getProperty('_filters');
        $_filters->setAccessible(true);
        $_filters = $_filters->getValue($this->sphinx);

        $this->assertInternalType('array',$_filters[0]);
        $this->assertEquals('year',$_filters[0]['attr']);
        $this->assertEquals(array(2014),$_filters[0]['values']);
        $this->assertFalse($_filters[0]['exclude']);
    }

    public function testSetFilterRangeErrorAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterRange( NULL, 2000, 2040 );
    }

    public function testSetFilterRangeErrorMinIsNotNumeric()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterRange( 'year' , NULL, 2040 );
    }

    public function testSetFilterRangeErrorMaxIsNotNumeric()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterRange( 'year', 2000, NULL );
    }

    public function testSetFilterRangeErrorMinIsGreaterThanMax()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterRange( 'year', 2040, 2000 );
    }

    public function testSetFilterRange()
    {
        $instance = $this->sphinx->setFilterRange( 'year', 2000, 2040 );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_filters = $reflectionClass->getProperty('_filters');
        $_filters->setAccessible(true);
        $_filters = $_filters->getValue($this->sphinx);

        $this->assertInternalType('array',$_filters[0]);
        $this->assertEquals('year',$_filters[0]['attr']);
        $this->assertEquals(2000,$_filters[0]['min']);
        $this->assertEquals(2040,$_filters[0]['max']);
        $this->assertFalse($_filters[0]['exclude']);
    }

    public function testSetFilterFloatRangeErrorAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterFloatRange( NULL, 6.5, 7.5 );
    }

    public function testSetFilterFloatRangeErrorMinIsNotNumeric()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterFloatRange( 'float_attribute' , NULL, 7.5 );
    }

    public function testSetFilterFloatRangeErrorMaxIsNotNumeric()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterFloatRange( 'float_attribute', 6.5, NULL );
    }

    public function testSetFilterFloatRangeErrorMinIsGreaterThanMax()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setFilterFloatRange( 'float_attribute', 7.5, 6.5 );
    }

    public function testSetFilterFloatRange()
    {
        $instance = $this->sphinx->setFilterFloatRange( 'float_attribute', 6.5, 7.5 );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_filters = $reflectionClass->getProperty('_filters');
        $_filters->setAccessible(true);
        $_filters = $_filters->getValue($this->sphinx);

        $this->assertInternalType('array',$_filters[0]);
        $this->assertEquals('float_attribute',$_filters[0]['attr']);
        $this->assertEquals(6.5,$_filters[0]['min']);
        $this->assertEquals(7.5,$_filters[0]['max']);
        $this->assertFalse($_filters[0]['exclude']);
    }

    public function testSetGeoAnchorLatitudeAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGeoAnchor( NULL, 'lon_attr', 7.5, 6.5 );
    }

    public function testSetGeoAnchorLongitudeAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGeoAnchor( 'lat_attr', NULL, 7.5, 6.5 );
    }

    public function testSetGeoAnchorLatitudeValueIsNotFloat()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGeoAnchor( 'lat_attr', 'lon_attr', 7, 6.5 );
    }

    public function testSetGeoAnchorLongitudeValueIsNotFloat()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGeoAnchor( 'lat_attr', 'lon_attr', 7.5, 6 );
    }

    public function testSetGeoAnchor()
    {
        $instance = $this->sphinx->setGeoAnchor( 'lat_attr', 'lon_attr', 7.5, 6.5 );

        $this->assertInstanceOf($this->instanceName,$instance);


        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_anchor = $reflectionClass->getProperty('_anchor');
        $_anchor->setAccessible(true);
        $_anchor = $_anchor->getValue($this->sphinx);

        $this->assertInternalType('array',$_anchor);
        $this->assertEquals(6.5,$_anchor['long']);
        $this->assertEquals(7.5,$_anchor['lat']);
        $this->assertEquals('lat_attr',$_anchor['attrlat']);
        $this->assertEquals('lon_attr',$_anchor['attrlong']);
    }

    public function testSetGroupByErrorAttributeNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGroupBy( NULL, SPH_GROUPBY_DAY);
    }

    public function testSetGroupByErrorGroupSortNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGroupBy( 'year', SPH_GROUPBY_DAY, NULL);
    }

    public function testSetGroupByErrorGroupByNotValid()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGroupBy( 'year', 1000000000);
    }

    public function testSetGroupBy_SPH_GROUPBY_DAY()
    {
        $instance = $this->sphinx->setGroupBy( 'year', SPH_GROUPBY_DAY);

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_groupby = $reflectionClass->getProperty('_groupby');
        $_groupby->setAccessible(true);
        $_groupby = $_groupby->getValue($this->sphinx);

        $_groupfunc = $reflectionClass->getProperty('_groupfunc');
        $_groupfunc->setAccessible(true);
        $_groupfunc = $_groupfunc->getValue($this->sphinx);

        $_groupsort = $reflectionClass->getProperty('_groupsort');
        $_groupsort->setAccessible(true);
        $_groupsort = $_groupsort->getValue($this->sphinx);

        $this->assertEquals('year',$_groupby);
        $this->assertEquals(SPH_GROUPBY_DAY,$_groupfunc);
        $this->assertEquals("@group desc",$_groupsort);
    }

    public function testSetGroupBy_SPH_GROUPBY_WEEK()
    {
        $instance = $this->sphinx->setGroupBy( 'year', SPH_GROUPBY_WEEK);

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_groupby = $reflectionClass->getProperty('_groupby');
        $_groupby->setAccessible(true);
        $_groupby = $_groupby->getValue($this->sphinx);

        $_groupfunc = $reflectionClass->getProperty('_groupfunc');
        $_groupfunc->setAccessible(true);
        $_groupfunc = $_groupfunc->getValue($this->sphinx);

        $_groupsort = $reflectionClass->getProperty('_groupsort');
        $_groupsort->setAccessible(true);
        $_groupsort = $_groupsort->getValue($this->sphinx);

        $this->assertEquals('year',$_groupby);
        $this->assertEquals(SPH_GROUPBY_WEEK,$_groupfunc);
        $this->assertEquals("@group desc",$_groupsort);
    }

    public function testSetGroupBy_SPH_GROUPBY_MONTH()
    {
        $instance = $this->sphinx->setGroupBy( 'year', SPH_GROUPBY_MONTH);

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_groupby = $reflectionClass->getProperty('_groupby');
        $_groupby->setAccessible(true);
        $_groupby = $_groupby->getValue($this->sphinx);

        $_groupfunc = $reflectionClass->getProperty('_groupfunc');
        $_groupfunc->setAccessible(true);
        $_groupfunc = $_groupfunc->getValue($this->sphinx);

        $_groupsort = $reflectionClass->getProperty('_groupsort');
        $_groupsort->setAccessible(true);
        $_groupsort = $_groupsort->getValue($this->sphinx);

        $this->assertEquals('year',$_groupby);
        $this->assertEquals(SPH_GROUPBY_MONTH,$_groupfunc);
        $this->assertEquals("@group desc",$_groupsort);
    }

    public function testSetGroupBy_SPH_GROUPBY_YEAR()
    {
        $instance = $this->sphinx->setGroupBy( 'year', SPH_GROUPBY_YEAR);

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_groupby = $reflectionClass->getProperty('_groupby');
        $_groupby->setAccessible(true);
        $_groupby = $_groupby->getValue($this->sphinx);

        $_groupfunc = $reflectionClass->getProperty('_groupfunc');
        $_groupfunc->setAccessible(true);
        $_groupfunc = $_groupfunc->getValue($this->sphinx);

        $_groupsort = $reflectionClass->getProperty('_groupsort');
        $_groupsort->setAccessible(true);
        $_groupsort = $_groupsort->getValue($this->sphinx);

        $this->assertEquals('year',$_groupby);
        $this->assertEquals(SPH_GROUPBY_YEAR,$_groupfunc);
        $this->assertEquals("@group desc",$_groupsort);
    }

    public function testSetGroupBy_SPH_GROUPBY_ATTR()
    {
        $instance = $this->sphinx->setGroupBy( 'year', SPH_GROUPBY_ATTR);

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_groupby = $reflectionClass->getProperty('_groupby');
        $_groupby->setAccessible(true);
        $_groupby = $_groupby->getValue($this->sphinx);

        $_groupfunc = $reflectionClass->getProperty('_groupfunc');
        $_groupfunc->setAccessible(true);
        $_groupfunc = $_groupfunc->getValue($this->sphinx);

        $_groupsort = $reflectionClass->getProperty('_groupsort');
        $_groupsort->setAccessible(true);
        $_groupsort = $_groupsort->getValue($this->sphinx);

        $this->assertEquals('year',$_groupby);
        $this->assertEquals(SPH_GROUPBY_ATTR,$_groupfunc);
        $this->assertEquals("@group desc",$_groupsort);
    }

    public function testSetGroupBy_SPH_GROUPBY_ATTRPAIR()
    {
        $instance = $this->sphinx->setGroupBy( 'year', SPH_GROUPBY_ATTRPAIR);

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_groupby = $reflectionClass->getProperty('_groupby');
        $_groupby->setAccessible(true);
        $_groupby = $_groupby->getValue($this->sphinx);

        $_groupfunc = $reflectionClass->getProperty('_groupfunc');
        $_groupfunc->setAccessible(true);
        $_groupfunc = $_groupfunc->getValue($this->sphinx);

        $_groupsort = $reflectionClass->getProperty('_groupsort');
        $_groupsort->setAccessible(true);
        $_groupsort = $_groupsort->getValue($this->sphinx);

        $this->assertEquals('year',$_groupby);
        $this->assertEquals(SPH_GROUPBY_ATTRPAIR,$_groupfunc);
        $this->assertEquals("@group desc",$_groupsort);
    }

    public function testSetGroupDistinctAttributeNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setGroupDistinct( 1 );
    }

    public function testSetGroupDistinct()
    {
        $instance = $this->sphinx->setGroupDistinct( 'year' );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_groupdistinct = $reflectionClass->getProperty('_groupdistinct');
        $_groupdistinct->setAccessible(true);
        $_groupdistinct = $_groupdistinct->getValue($this->sphinx);

        $this->assertEquals('year',$_groupdistinct);
    }

    public function testSetRetriesErrorCountIsNotInteger()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setRetries( 'A' );
    }

    public function testSetRetriesErrorDelayIsNotInteger()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setRetries( 2, 'A' );
    }

    public function testSetRetriesErrorCountIsNegativeInteger()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setRetries( -2 );
    }

    public function testSetRetriesErrorDelayIsNegativeInteger()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setRetries( 2, -2 );
    }

    public function testSetRetries()
    {
        $instance = $this->sphinx->setRetries( 5, 1 );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_retrycount = $reflectionClass->getProperty('_retrycount');
        $_retrycount->setAccessible(true);
        $_retrycount = $_retrycount->getValue($this->sphinx);

        $_retrydelay = $reflectionClass->getProperty('_retrydelay');
        $_retrydelay->setAccessible(true);
        $_retrydelay = $_retrydelay->getValue($this->sphinx);

        $this->assertEquals(5,$_retrycount);
        $this->assertEquals(1,$_retrydelay);

    }

    public function testSetArrayResult()
    {
        $instance = $this->sphinx->setArrayResult( true );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_arrayresult = $reflectionClass->getProperty('_arrayresult');
        $_arrayresult->setAccessible(true);
        $_arrayresult = $_arrayresult->getValue($this->sphinx);

        $this->assertTrue($_arrayresult);
    }

    public function testSetArrayResultErrorParamIsNotBoolean()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setArrayResult( 2 );
    }

    public function testSetOverrideErrorAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setOverride( NULL, SPH_ATTR_INTEGER, array(2004,2005,2006,2007));
    }

    public function testSetOverrideErrorAttributeTypeIsNotValid()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setOverride( 'year', 100000, array(2004,2005,2006,2007));
    }

    public function testSetOverrideErrorAttributeIsNotArray()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setOverride( 'year', SPH_ATTR_INTEGER, 2004);
    }

    public function testSetOverride()
    {
        $instance = $this->sphinx->setOverride( 'year', SPH_ATTR_INTEGER, array(2004,2005,2006,2007));

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_overrides = $reflectionClass->getProperty('_overrides');
        $_overrides->setAccessible(true);
        $_overrides = $_overrides->getValue($this->sphinx);

        $this->assertArrayHasKey('year',$_overrides);
        $this->assertEquals('year',$_overrides['year']['attr']);
        $this->assertEquals(SPH_ATTR_INTEGER,$_overrides['year']['type']);
        $this->assertEquals(array(2004,2005,2006,2007),$_overrides['year']['values']);
    }

    public function testSetSelectErrorIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setSelect(NULL);
    }

    public function testSetSelect()
    {
        $instance = $this->sphinx->setSelect( "*, @weight+(user_karma+ln(pageviews))*0.1 AS myweight" );

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_select = $reflectionClass->getProperty('_select');
        $_select->setAccessible(true);
        $_select = $_select->getValue($this->sphinx);

        $this->assertEquals("*, @weight+(user_karma+ln(pageviews))*0.1 AS myweight",$_select);
    }

    public function testResetFilters()
    {
        $instance = $this->sphinx->resetFilters();

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_filters = $reflectionClass->getProperty('_filters');
        $_filters->setAccessible(true);
        $_filters = $_filters->getValue($this->sphinx);

        $_anchor = $reflectionClass->getProperty('_anchor');
        $_anchor->setAccessible(true);
        $_anchor = $_anchor->getValue($this->sphinx);

        $this->assertEquals(array(),$_filters);
        $this->assertEquals(array(),$_anchor);
    }

    public function testResetGroupBy()
    {
        $instance = $this->sphinx->resetGroupBy();

        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_groupby = $reflectionClass->getProperty('_groupby');
        $_groupby->setAccessible(true);
        $_groupby = $_groupby->getValue($this->sphinx);

        $groupfunc = $reflectionClass->getProperty('_groupfunc');
        $groupfunc->setAccessible(true);
        $groupfunc = $groupfunc->getValue($this->sphinx);

        $_groupsort = $reflectionClass->getProperty('_groupsort');
        $_groupsort->setAccessible(true);
        $_groupsort = $_groupsort->getValue($this->sphinx);

        $_groupdistinct = $reflectionClass->getProperty('_groupdistinct');
        $_groupdistinct->setAccessible(true);
        $_groupdistinct = $_groupdistinct->getValue($this->sphinx);

        $this->assertEquals("",$_groupby);
        $this->assertEquals(SPH_GROUPBY_DAY,$groupfunc);
        $this->assertEquals("@group desc",$_groupsort);
        $this->assertEquals("",$_groupdistinct);
    }

    public function testResetOverrides()
    {
        $instance = $this->sphinx->resetOverrides();
        $this->assertInstanceOf($this->instanceName,$instance);

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_overrides = $reflectionClass->getProperty('_overrides');
        $_overrides->setAccessible(true);
        $_overrides = $_overrides->getValue($this->sphinx);

        $this->assertEquals(array(),$_overrides);
    }

    public function testAddQuery()
    {
        $actual = $this->sphinx->addQuery("some search terms 1");
        $this->assertEquals(0,$actual);

        $actual = $this->sphinx->addQuery("some search terms 2");
        $this->assertEquals(1,$actual);

        $actual = $this->sphinx->addQuery("some search terms 3");
        $this->assertEquals(2,$actual);
    }

    public function testAddQueryWithIndex()
    {
        $actual = $this->sphinx->addQuery("some search terms 1",'movies1');
        $this->assertEquals(0,$actual);

        $actual = $this->sphinx->addQuery("some search terms 2",'movies2');
        $this->assertEquals(1,$actual);

        $actual = $this->sphinx->addQuery("some search terms 3",'movies3');
        $this->assertEquals(2,$actual);

    }

    public function testAddQueryWithIndexAndComment()
    {
        $actual = $this->sphinx->addQuery("some search terms1",'movies1','This query fetches movie titles.');
        $this->assertEquals(0,$actual);

        $actual = $this->sphinx->addQuery("some search terms2",'movies2','This query fetches movie titles.');
        $this->assertEquals(1,$actual);

        $actual = $this->sphinx->addQuery("some search terms3",'movies3','This query fetches movie titles.');
        $this->assertEquals(2,$actual);
    }


    public function testRunQueries()
    {

    }

    public function testBuildExcerptsErrorDocsIsNotArray()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->buildExcerpts ( NULL, 'index', 'some keywords', array() );
    }

    public function testBuildExcerptsErrorIndexIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->buildExcerpts ( array(), NULL, 'some keywords', array() );
    }

    public function testBuildExcerptsErrorWordsIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->buildExcerpts ( array(), 'index', NULL, array() );
    }

    public function testBuildExcerptsErrorOptionsIsNotArray()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->buildExcerpts ( array(), 'index', 'some keywords', NULL);
    }

    public function testBuildExcerptsFailsOnSearchd()
    {
        $docs = array();
        $index = 'movies';
        $words = 'The Amazing Spiderman';

        $actual = $this->sphinx
            ->setServer('2013.192.168.0.1')
            ->buildExcerpts ( $docs, $index, $words )
        ;

        $this->assertEquals(false,$actual);
    }

    /**
     * Needs a valid Sphinx.conf loaded to indexer to be tested properly.
     */
    public function testBuildExcerpts()
    {
        /*
            $docs = array(1,2,3);
            $index = 'movies';
            $words = 'The Amazing Spiderman';

            $actual = $this->sphinx->buildExcerpts( $docs, $index, $words );

            $this->assertInternalType('array',$actual);
        */
    }

    /**
     * Needs a valid Sphinx.conf loaded to indexer to be tested properly.
     */
    public function testBuildKeywords()
    {

    }

    public function testEscapeStringAtSymbol()
    {
        $actual = $this->sphinx->escapeString('@groupby \\at~ and & (symbols) mu-st "be" es/ca=ped ^__^$ ');
        $this->assertEquals('\@groupby \\\\at\~ and \& \(symbols\) mu\-st \"be\" es\/ca\=ped \^__\^\$ ',$actual);
    }

    public function testUpdateAttributes()
    {

    }

    /**
     * Needs a valid Sphinx.conf loaded to indexer to be tested properly.
     */
    public function testCloseWhenConnectionNotEstablished()
    {
        $this->sphinx->setServer('2013.192.168.0.1');
        $this->sphinx->open();
        $this->assertFalse($this->sphinx->close());
    }

    /**
     * Needs a valid Sphinx.conf loaded to indexer to be tested properly.
     */
    public function testCloseWhenConnectionEstablished()
    {
        $this->sphinx->open();
        $this->assertTrue($this->sphinx->close());
    }

    public function testCloseWhenConnectionEstablishedWithWrongData()
    {
        $this->sphinx
            ->setServer('2013.192.168.0.1')
            ->query('test')
        ;

        $actual = $this->sphinx->status();
        $this->assertInternalType('boolean',$actual);
        $this->assertFalse($actual);
    }


    public function testFlushAttributesOK()
    {
        //$this->assertEquals("",$this->sphinx->getLastError());
    }

    public function testFlushAttributesKO()
    {
      /*  $actual = $this->sphinx->flushAttributes();

        $this->assertEquals(-1,$actual);
        $this->assertEquals("unexpected response length",$this->sphinx->getLastError());
      */
    }
}
