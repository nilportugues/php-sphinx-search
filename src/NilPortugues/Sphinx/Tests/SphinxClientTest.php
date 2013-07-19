<?php

/**
 * Testing the SphinxClient class.
 * This does not test the search results returned by searchd.
 */
class SphinxClientTest extends \PHPUnit_Framework_TestCase
{
    protected $sphinx;

    public function setUp()
    {
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

        $this->sphinx->removeFilter('year');

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
        $this->sphinx->setServer(SPHINX_HOST,80);
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
        $this->sphinx->setServer(SPHINX_HOST);
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

    }

    public function testSetLimits()
    {
        $this->sphinx->setLimits( 10, 100, 1000, 500 );
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
        $this->sphinx->setMaxQueryTime( 10 );
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
        $this->sphinx->setMatchMode(SPH_MATCH_ALL);

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_ALL,$_mode->getValue($this->sphinx));
    }

    public function testSetMatchMode_SPH_MATCH_ANY()
    {
        $this->sphinx->setMatchMode(SPH_MATCH_ANY);
        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_ANY,$_mode->getValue($this->sphinx));
    }

    public function testSetMatchMode_SPH_MATCH_PHRASE()
    {
        $this->sphinx->setMatchMode(SPH_MATCH_PHRASE);
        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_PHRASE,$_mode->getValue($this->sphinx));
    }

    public function testSetMatchMode_SPH_MATCH_BOOLEAN()
    {
        $this->sphinx->setMatchMode(SPH_MATCH_BOOLEAN);
        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_BOOLEAN,$_mode->getValue($this->sphinx));
    }

    public function testSetMatchMode_SPH_MATCH_EXTENDED()
    {
        $this->sphinx->setMatchMode(SPH_MATCH_EXTENDED);
        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_EXTENDED,$_mode->getValue($this->sphinx));
    }

    public function testSetMatchMode_SPH_MATCH_FULLSCAN()
    {
        $this->sphinx->setMatchMode(SPH_MATCH_FULLSCAN);
        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_FULLSCAN,$_mode->getValue($this->sphinx));
    }

    public function testSetMatchMode_SPH_MATCH_EXTENDED2()
    {
        $this->sphinx->setMatchMode(SPH_MATCH_EXTENDED2);
        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_mode = $reflectionClass->getProperty('_mode');
        $_mode->setAccessible(true);

        $this->assertEquals(SPH_MATCH_EXTENDED2,$_mode->getValue($this->sphinx));
    }

    public function testSetRankingMode_SPH_RANK_EXPR()
    {
        $this->sphinx->setRankingMode( SPH_RANK_EXPR );

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_EXPR,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_FIELDMASK()
    {
        $this->sphinx->setRankingMode( SPH_RANK_FIELDMASK );

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_FIELDMASK,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_MATCHANY()
    {
        $this->sphinx->setRankingMode( SPH_RANK_MATCHANY );

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_MATCHANY,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_WORDCOUNT()
    {
        $this->sphinx->setRankingMode( SPH_RANK_WORDCOUNT );

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_WORDCOUNT,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_PROXIMITY()
    {
        $this->sphinx->setRankingMode( SPH_RANK_PROXIMITY );

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_PROXIMITY,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_PROXIMITY_BM25()
    {
        $this->sphinx->setRankingMode( SPH_RANK_PROXIMITY_BM25 );

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_PROXIMITY_BM25,$_ranker->getValue($this->sphinx));

    }


    public function testSetRankingMode_SPH_RANK_BM25()
    {
        $this->sphinx->setRankingMode( SPH_RANK_BM25 );

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_BM25,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_NONE()
    {
        $this->sphinx->setRankingMode( SPH_RANK_NONE );

        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_ranker = $reflectionClass->getProperty('_ranker');
        $_ranker->setAccessible(true);
        $this->assertEquals(SPH_RANK_NONE,$_ranker->getValue($this->sphinx));

    }

    public function testSetRankingMode_SPH_RANK_SPH04()
    {
        $this->sphinx->setRankingMode( SPH_RANK_SPH04, "Some valid rank expression" );

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
        $this->sphinx->setRankingMode( 100000000 );
    }

    public function testSetRankingModeErrorAttributeIsNotString()
    {
        $this->setExpectedException('\PHPUnit_Framework_Error');
        $this->sphinx->setRankingMode( SPH_RANK_SPH04, NULL );
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
        $this->sphinx->SetFieldWeights($weights);

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
        $this->sphinx->SetIndexWeights($weights);

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
        $this->sphinx->setSortMode( "SPH_SORT_ATTR_DESC" );

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
        $this->sphinx->setSortMode( SPH_SORT_RELEVANCE );

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
        $this->sphinx->setSortMode(SPH_SORT_EXPR, "@weight + fulltext_field*200");

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
        $this->sphinx->setSortMode(SPH_SORT_ATTR_DESC,'year');

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
        $this->sphinx->setSortMode(SPH_SORT_ATTR_ASC,'year');

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
        $this->sphinx->setSortMode(SPH_SORT_TIME_SEGMENTS,'year');

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
        $this->sphinx->setSortMode(SPH_SORT_EXTENDED,'@relevance DESC, year DESC, @id DESC');

        $reflectionClass = new \ReflectionClass($this->sphinx);
        $_sort = $reflectionClass->getProperty('_sort');
        $_sort->setAccessible(true);
        $_sortby = $reflectionClass->getProperty('_sortby');
        $_sortby->setAccessible(true);

        $this->assertEquals(SPH_SORT_EXTENDED,$_sort->getValue($this->sphinx));
        $this->assertEquals("@relevance DESC, year DESC, @id DESC",$_sortby->getValue($this->sphinx));
    }




    public function testSetIDRange()
    {

    }








    public function testSetFilterWithoutExcludeFlag()
    {
        $this->sphinx->setFilter('year',array(2014));
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
        $this->sphinx->setFilter('year',array(2014),true);
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
        $this->sphinx->setFilter('year',array(2014),1);
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
        $this->sphinx->setFilter('year',array(2014),0);
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
        $this->sphinx->setFilter('year',array(2014),'ThisShouldBeConvertedToFalse');
        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_filters = $reflectionClass->getProperty('_filters');
        $_filters->setAccessible(true);
        $_filters = $_filters->getValue($this->sphinx);

        $this->assertInternalType('array',$_filters[0]);
        $this->assertEquals('year',$_filters[0]['attr']);
        $this->assertEquals(array(2014),$_filters[0]['values']);
        $this->assertFalse($_filters[0]['exclude']);
    }





    public function testSetFilterRange()
    {

    }

    public function testSetFilterFloatRange()
    {

    }

    public function testSetGeoAnchor()
    {

    }

    public function testSetGroupBy()
    {

    }

    public function testSetGroupDistinct()
    {

    }

    public function testSetRetries()
    {

    }

    public function testSetArrayResult()
    {

    }

    public function testSetOverride()
    {

    }

    public function testSetSelect()
    {

    }

    public function testResetFilters()
    {

    }

    public function testResetGroupBy()
    {

    }

    public function testResetOverrides()
    {

    }

    public function testAddQuery()
    {

    }

    public function testRunQueries()
    {

    }

    public function testBuildExcerpts()
    {

    }

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

/*
    public function testCloseWhenConnectionNotEstablished()
    {
        $actual = $this->sphinx->status();
        $this->assertInternalType('boolean',$actual);
        $this->assertFalse($actual);
    }
*/
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

    public function testCloseWhenConnectionEstablished()
    {

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
