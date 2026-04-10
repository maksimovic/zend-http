<?php

use PHPUnit\Framework\TestCase;

class Zend_Http_Client_TestAdapterTest extends TestCase
{
    /**
     * @var Zend_Http_Client_Adapter_Test
     */
    protected $adapter;

    protected function setUp(): void
    {
        $this->adapter = new Zend_Http_Client_Adapter_Test();
    }

    protected function tearDown(): void
    {
        $this->adapter = null;
    }

    public function testSetConfigThrowsOnInvalidConfig(): void
    {
        $this->expectException('Zend_Http_Client_Adapter_Exception');
        $this->adapter->setConfig('foo');
    }

    public function testSetConfigReturnsQuietly(): void
    {
        $this->adapter->setConfig(array('foo' => 'bar'));
        $this->assertTrue(true);
    }

    public function testConnectReturnsQuietly(): void
    {
        $this->adapter->connect('http://foo');
        $this->assertTrue(true);
    }

    public function testCloseReturnsQuietly(): void
    {
        $this->adapter->close();
        $this->assertTrue(true);
    }

    public function testFailRequestOnDemand(): void
    {
        $this->adapter->setNextRequestWillFail(true);

        try {
            $this->adapter->connect('http://foo');
            $this->fail();
        } catch (Zend_Http_Client_Adapter_Exception $e) {
            // Connect again to see that the next request does not fail
            $this->adapter->connect('http://foo');
            $this->assertTrue(true, 'Second connect should succeed');
        }
    }

    public function testReadDefaultResponse(): void
    {
        $expected = "HTTP/1.1 400 Bad Request\r\n\r\n";
        $this->assertEquals($expected, $this->adapter->read());
    }

    public function testReadingSingleResponse(): void
    {
        $expected = "HTTP/1.1 200 OK\r\n\r\n";
        $this->adapter->setResponse($expected);
        $this->assertEquals($expected, $this->adapter->read());
        $this->assertEquals($expected, $this->adapter->read());
    }

    public function testReadingResponseCycles(): void
    {
        $expected = array("HTTP/1.1 200 OK\r\n\r\n",
                          "HTTP/1.1 302 Moved Temporarily\r\n\r\n");

        $this->adapter->setResponse($expected[0]);
        $this->adapter->addResponse($expected[1]);

        $this->assertEquals($expected[0], $this->adapter->read());
        $this->assertEquals($expected[1], $this->adapter->read());
        $this->assertEquals($expected[0], $this->adapter->read());
    }

    /**
     * @dataProvider validHttpResponseProvider
     */
    public function testAddResponseAsString($testResponse): void
    {
        $this->adapter->read(); // pop out first response

        $this->adapter->addResponse($testResponse);
        $this->assertEquals($testResponse, $this->adapter->read());
    }

    /**
     * @dataProvider validHttpResponseProvider
     */
    public function testAddResponseAsObject($testResponse): void
    {
        $this->adapter->read(); // pop out first response

        $respObj = Zend_Http_Response::fromString($testResponse);

        $this->adapter->addResponse($respObj);
        $this->assertEquals($testResponse, $this->adapter->read());
    }

    public function testReadingResponseCyclesWhenSetByArray(): void
    {
        $expected = array("HTTP/1.1 200 OK\r\n\r\n",
                          "HTTP/1.1 302 Moved Temporarily\r\n\r\n");

        $this->adapter->setResponse($expected);

        $this->assertEquals($expected[0], $this->adapter->read());
        $this->assertEquals($expected[1], $this->adapter->read());
        $this->assertEquals($expected[0], $this->adapter->read());
    }

    public function testSettingNextResponseByIndex(): void
    {
        $expected = array("HTTP/1.1 200 OK\r\n\r\n",
                          "HTTP/1.1 302 Moved Temporarily\r\n\r\n",
                          "HTTP/1.1 404 Not Found\r\n\r\n");

        $this->adapter->setResponse($expected);
        $this->assertEquals($expected[0], $this->adapter->read());

        foreach ($expected as $i => $expected) {
            $this->adapter->setResponseIndex($i);
            $this->assertEquals($expected, $this->adapter->read());
        }
    }

    public function testSettingNextResponseToAnInvalidIndex(): void
    {
        $indexes = array(-1, 1);
        foreach ($indexes as $i) {
            try {
                $this->adapter->setResponseIndex($i);
                $this->fail();
            } catch (Exception $e) {
                $class = 'Zend_Http_Client_Adapter_Exception';
                $this->assertTrue($e instanceof $class);
                $this->assertMatchesRegularExpression('/out of range/i', $e->getMessage());
            }
        }
    }

    public function testGetConfig(): void
    {
        $this->assertNotNull($this->adapter->getConfig());
    }

    public static function validHttpResponseProvider(): array
    {
        return array(
           array("HTTP/1.1 200 OK\r\n\r\n"),
           array("HTTP/1.1 302 Moved Temporarily\r\nLocation: http://example.com/baz\r\n\r\n"),
           array("HTTP/1.1 404 Not Found\r\n" .
                 "Date: Sun, 14 Jun 2009 10:40:06 GMT\r\n" .
                 "Server: Apache/2.2.3 (CentOS)\r\n" .
                 "Content-length: 281\r\n" .
                 "Connection: close\r\n" .
                 "Content-type: text/html; charset=iso-8859-1\r\n\r\n" .
                 "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n" .
                 "<html><head>\n" .
                 "<title>404 Not Found</title>\n" .
                 "</head><body>\n" .
                 "<h1>Not Found</h1>\n" .
                 "<p>The requested URL /foo/bar was not found on this server.</p>\n" .
                 "<hr>\n" .
                 "<address>Apache/2.2.3 (CentOS) Server at example.com Port 80</address>\n" .
                 "</body></html>")
        );
    }
}
