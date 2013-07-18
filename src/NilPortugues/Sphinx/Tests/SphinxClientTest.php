<?php

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
        $this->sphinx->setServer('NotAValidServerIp');

        $this->assertEquals('',$this->sphinx->getLastError());
    }


    public function testGetLastErrorWhenConnectionNotEstablished()
    {

    }


    public function testGetLastWarning()
    {

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

    public function testIsConnectErrorNoConnectionInitialized()
    {
        $this->sphinx
            ->setServer(SPHINX_HOST,SPHINX_PORT)
            ->query('test')
        ;
        $actual = $this->sphinx->isConnectError();
        $this->assertFalse($actual);
    }

    public function testSetServerHostAndPort()
    {
        $this->sphinx->setServer(SPHINX_HOST,SPHINX_PORT);
        $reflectionClass = new \ReflectionClass($this->sphinx);

        $_path = $reflectionClass->getProperty('_path');
        $_path->setAccessible(true);

        $_port = $reflectionClass->getProperty('_port');
        $_port->setAccessible(true);

        $this->assertEquals( '', $_path->getValue($this->sphinx) );
        $this->assertEquals( SPHINX_PORT, $_port->getValue($this->sphinx) );
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

    }

    public function testSetMaxQueryTime()
    {

    }

    public function testSetMatchMode()
    {

    }

    public function testSetRankingMode()
    {

    }

    public function testSetSortMode()
    {

    }

    public function testSetWeights()
    {

    }

    public function testSetFieldWeights()
    {

    }

    public function testSetIndexWeights()
    {

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

    public function testStatusWhenOK()
    {
        $actual = $this->sphinx->status();
        $this->assertInternalType('array',$actual);
    }

    public function testCloseWhenConnectionNotEstablished()
    {
        $actual = $this->sphinx->close();
        $this->assertInternalType('boolean',$actual);
        $this->assertFalse($actual);
        $this->assertEquals('not connected',$this->sphinx->getLastError());
    }

    public function testCloseWhenConnectionEstablished()
    {

    }

    public function testFlushAttributes()
    {

    }
}
