<?php

use PHPUnit\Framework\TestCase;

class Zend_Http_Client_StaticTest extends TestCase
{
    /**
     * @var Zend_Http_Client
     */
    protected $_client = null;

    protected function setUp(): void
    {
        $this->_client = new Zend_Http_Client_StaticTest_Mock('http://www.example.com');
    }

    protected function tearDown(): void
    {
        $this->_client = null;
    }

    /**
     * URI Tests
     */

    public function testSetGetUriString(): void
    {
        $uristr = 'http://www.zend.com:80/';

        $this->_client->setUri($uristr);

        $uri = $this->_client->getUri();
        $this->assertTrue($uri instanceof Zend_Uri_Http, 'Returned value is not a Uri object as expected');
        $this->assertEquals($uri->__toString(), $uristr, 'Returned Uri object does not hold the expected URI');

        $uri = $this->_client->getUri(true);
        $this->assertIsString($uri, 'Returned value expected to be a string, ' . gettype($uri) . ' returned');
        $this->assertEquals($uri, $uristr, 'Returned string is not the expected URI');
    }

    public function testSetGetUriObject(): void
    {
        $uriobj = Zend_Uri::factory('http://www.zend.com:80/');

        $this->_client->setUri($uriobj);

        $uri = $this->_client->getUri();
        $this->assertTrue($uri instanceof Zend_Uri_Http, 'Returned value is not a Uri object as expected');
        $this->assertEquals($uri, $uriobj, 'Returned object is not the excepted Uri object');
    }

    public function testInvalidUriStringException(): void
    {
        $this->expectException('Zend_Uri_Exception');
        $this->_client->setUri('httpp://__invalid__.com');
    }

    public function testInvalidUriObjectException(): void
    {
        try {
            $uri = Zend_Uri::factory('mailto:nobody@example.com');
            $this->_client->setUri($uri);
            $this->fail('Excepted invalid URI object exception was not thrown');
        } catch (Zend_Http_Client_Exception $e) {
            // We're good
        } catch (Zend_Uri_Exception $e) {
            $this->markTestIncomplete('Zend_Uri_Mailto is not implemented yet');
        }
    }

    public function testDoubleGetParameter(): void
    {
        $qstr = 'foo=bar&foo=baz';

        $this->_client->setUri('http://example.com/test/?' . $qstr);
        $this->_client->setAdapter('Zend_Http_Client_Adapter_Test');

        $res = $this->_client->request('GET');
        $this->assertStringContainsString($qstr, $this->_client->getLastRequest(),
            'Request is expected to contain the entire query string');
    }

    /**
     * Header Tests
     */

    public function testInvalidHeaderExcept(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        $this->_client->setHeaders('Ina_lid* Hea%der', 'is not good');
    }

    public function testInvalidHeaderNonStrictMode(): void
    {
        // Disable strict validation
        $this->_client->setConfig(array('strict' => false));

        try {
            $this->_client->setHeaders('Ina_lid* Hea%der', 'is not good');
        } catch (Zend_Http_Client_Exception $e) {
            $this->fail('Invalid header names should be allowed in non-strict mode');
        }
        $this->assertTrue(true);
    }

    public function testGetHeader(): void
    {
        $this->_client->setHeaders(array(
            'Accept-encoding' => 'gzip,deflate',
            'Accept-language' => 'en,de,*',
        ));

        $this->assertEquals($this->_client->getHeader('Accept-encoding'), 'gzip,deflate', 'Returned value of header is not as expected');
        $this->assertEquals($this->_client->getHeader('X-Fake-Header'), null, 'Non-existing header should not return a value');
    }

    public function testUnsetHeader(): void
    {
        $this->_client->setHeaders('Accept-Encoding', 'gzip,deflate');
        $this->_client->setHeaders('Accept-Encoding', null);
        $this->assertNull($this->_client->getHeader('Accept-encoding'), 'Returned value of header is expected to be null');
    }

    /**
     * Authentication tests
     */

    public function testExceptUnsupportedAuthDynamic(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        $this->_client->setAuth('shahar', '1234', 'SuperStrongAlgo');
    }

    public function testExceptUnsupportedAuthStatic(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        Zend_Http_Client::encodeAuthHeader('shahar', '1234', 'SuperStrongAlgo');
    }

    /**
     * Cookie and Cookie Jar tests
     */

    public function testSetNewCookieJar(): void
    {
        $this->_client->setCookieJar();
        $this->_client->setCookie('cookie', 'value');
        $this->_client->setCookie('chocolate', 'chips');
        $jar = $this->_client->getCookieJar();

        $this->assertTrue($jar instanceof Zend_Http_CookieJar, '$jar is not an instance of Zend_Http_CookieJar as expected');
        $this->assertEquals(count($jar->getAllCookies()), 2, '$jar does not contain 2 cookies as expected');
    }

    public function testSetReadyCookieJar(): void
    {
        $jar = new Zend_Http_CookieJar();
        $jar->addCookie('cookie=value', 'http://www.example.com');
        $jar->addCookie('chocolate=chips; path=/foo', 'http://www.example.com');

        $this->_client->setCookieJar($jar);

        $this->assertEquals($jar, $this->_client->getCookieJar(), '$jar is not the client\'s cookie jar as expected');
    }

    public function testUnsetCookieJar(): void
    {
        $this->_client->setCookieJar();
        $this->_client->setCookie('cookie', 'value');
        $this->_client->setCookie('chocolate', 'chips');
        $jar = $this->_client->getCookieJar();

        $this->_client->setCookieJar(null);

        $this->assertNull($this->_client->getCookieJar(), 'Cookie jar is expected to be null but it is not');
    }

    public function testSetInvalidCookieJar(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        $this->_client->setCookieJar('cookiejar');
    }

    public function testCaptureCookiesNoEncodeZF1850(): void
    {
        $cookieName = "cookieWithSpecialChars";
        $cookieValue = "HID=XXXXXX&UN=XXXXXXX&UID=XXXXX";

        $adapter = new Zend_Http_Client_Adapter_Test();
        $adapter->setResponse(
            "HTTP/1.0 200 OK\r\n" .
            "Content-type: text/plain\r\n" .
            "Content-length: 2\r\n" .
            "Connection: close\r\n" .
            "Set-Cookie: $cookieName=$cookieValue; path=/\r\n" .
            "\r\n" .
            "OK"
        );

        $this->_client->setUri('http://example.example/test');
        $this->_client->setConfig(array(
            'adapter'       => $adapter,
            'encodecookies' => false
        ));

        $this->_client->setCookieJar();

        // First request is expected to set the cookie
        $this->_client->request();

        // Next request should contain the cookie
        $this->_client->request();

        $request = $this->_client->getLastRequest();
        if (! preg_match("/^Cookie: $cookieName=([^;]+)/m", $request, $match)) {
            $this->fail("Could not find cookie in request");
        }

        $this->assertEquals($cookieValue, $match[1]);
    }

    /**
     * Configuration Handling
     */

    public function testConfigSetAsArray(): void
    {
        $config = array(
            'timeout'    => 500,
            'someoption' => 'hasvalue'
        );

        $this->_client->setConfig($config);

        $hasConfig = $this->_client->config;
        foreach($config as $k => $v) {
            $this->assertEquals($v, $hasConfig[$k]);
        }
    }

    public function testConfigSetAsZendConfig(): void
    {
        if (!class_exists('Zend_Config')) {
            $this->markTestSkipped('Zend_Config is not available');
        }

        $config = new Zend_Config(array(
            'timeout'  => 400,
            'nested'   => array(
                'item' => 'value',
            )
        ));

        $this->_client->setConfig($config);

        $hasConfig = $this->_client->config;
        $this->assertEquals($config->timeout, $hasConfig['timeout']);
        $this->assertEquals($config->nested->item, $hasConfig['nested']['item']);
    }

    /**
     * @dataProvider      invalidConfigProvider
     */
    public function testConfigSetInvalid($config): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        $this->_client->setConfig($config);
    }

    public function testConfigPassToAdapterZF4557(): void
    {
        $adapter = new Zend_Http_Client_StaticTest_TestAdapter_Mock();

        $this->_client->setConfig(array('param' => 'value1'));
        $this->_client->setAdapter($adapter);
        $adapterCfg = $adapter->config;
        $this->assertEquals('value1', $adapterCfg['param']);

        $this->_client->setConfig(array('param' => 'value2'));
        $adapterCfg = $adapter->config;
        $this->assertEquals('value2', $adapterCfg['param']);
    }

    /**
     * Other Tests
     */

    public function testGetLastResponse(): void
    {
        $this->assertEquals(null, $this->_client->getLastResponse(),
            'getLastResponse() is still expected to return null');

        $this->_client->setUri('http://example.com/foo/bar');
        $this->_client->setAdapter('Zend_Http_Client_Adapter_Test');

        $response = $this->_client->request();
        $this->assertTrue(($response === $this->_client->getLastResponse()),
            'Response is expected to be identical to the result of getLastResponse()');
    }

    public function testGetLastResponseWhenNotStoring(): void
    {
        $this->_client->setUri('http://example.com/foo/bar');
        $this->_client->setAdapter('Zend_Http_Client_Adapter_Test');
        $this->_client->setConfig(array('storeresponse' => false));

        $response = $this->_client->request();

        $this->assertNull($this->_client->getLastResponse(),
            'getLastResponse is expected to be null when not storing');
    }

    public function testInvalidPostContentType(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        $this->_client->setEncType('x-foo/something-fake');
        $this->_client->setParameterPost('parameter', 'value');

        $this->_client->request('POST');
    }

    public function testSocketErrorException(): void
    {
        $this->markTestSkipped('Network test - requires connecting to an external host');
    }

    /**
     * @dataProvider validMethodProvider
     */
    public function testSettingExtendedMethod($method): void
    {
        try {
            $this->_client->setMethod($method);
        } catch (Exception $e) {
            $this->fail("An unexpected exception was thrown when setting request method to '{$method}'");
        }
        $this->assertTrue(true);
    }

    /**
     * @dataProvider invalidMethodProvider
     */
    public function testSettingInvalidMethodThrowsException($method): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        $this->_client->setMethod($method);
    }

    public function testFormDataEncodingWithMultiArrayZF7038(): void
    {
        $this->_client->setAdapter('Zend_Http_Client_Adapter_Test');
        $this->_client->setUri('http://example.com');
        $this->_client->setEncType(Zend_Http_Client::ENC_FORMDATA);

        $this->_client->setParameterPost('test', array(
            'v0.1',
            'v0.2',
            'k1' => 'v1.0',
            'k2' => array(
                'v2.1',
                'k2.1' => 'v2.1.0'
            )
        ));

        $this->_client->request('POST');

        $expectedLines = file(dirname(__FILE__) . '/_files/ZF7038-multipartarrayrequest.txt');
        $gotLines = explode("\n", $this->_client->getLastRequest());

        $this->assertEquals(count($expectedLines), count($gotLines));

        while (($expected = array_shift($expectedLines)) &&
               ($got = array_shift($gotLines))) {

            $expected = trim($expected);
            $got = trim($got);
            $this->assertMatchesRegularExpression("/^$expected$/", $got);
        }
    }

    public function testFormFileUpload(): void
    {
        $this->_client->setAdapter('Zend_Http_Client_Adapter_Test');
        $this->_client->setUri('http://example.com');
        $this->_client->setFileUpload('testFile.name', 'testFile', 'TESTDATA12345', 'text/plain');
        $this->_client->request('POST');

        $expectedLines = file(dirname(__FILE__) . '/_files/ZF4236-fileuploadrequest.txt');
        $gotLines = explode("\n", trim($this->_client->getLastRequest()));

        $this->assertEquals(count($expectedLines), count($gotLines));
        while (($expected = array_shift($expectedLines)) &&
               ($got = array_shift($gotLines))) {

            $expected = trim($expected);
            $got = trim($got);
            $this->assertMatchesRegularExpression("/^$expected$/", $got);
        }
    }

    public function testClientBodyRetainsFieldOrdering(): void
    {
        $this->_client->setAdapter('Zend_Http_Client_Adapter_Test');
        $this->_client->setUri('http://example.com');
        $this->_client->setParameterPost('testFirst', 'foo');
        $this->_client->setFileUpload('testFile.name', 'testFile', 'TESTDATA12345', 'text/plain');
        $this->_client->setParameterPost('testLast', 'bar');
        $this->_client->request('POST');

        $expectedLines = file(dirname(__FILE__) . '/_files/ZF4236-clientbodyretainsfieldordering.txt');
        $gotLines = explode("\n", trim($this->_client->getLastRequest()));

        $this->assertEquals(count($expectedLines), count($gotLines));
        while (($expected = array_shift($expectedLines)) &&
               ($got = array_shift($gotLines))) {

            $expected = trim($expected);
            $got = trim($got);
            $this->assertMatchesRegularExpression("/^$expected$/", $got);
        }
    }

    public function testMultibyteRawPostDataZF2098(): void
    {
        $this->_client->setAdapter('Zend_Http_Client_Adapter_Test');
        $this->_client->setUri('http://example.com');

        $bodyFile = dirname(__FILE__) . '/_files/ZF2098-multibytepostdata.txt';

        $this->_client->setRawData(file_get_contents($bodyFile), 'text/plain');
        $this->_client->request('POST');
        $request = $this->_client->getLastRequest();

        if (! preg_match('/^content-length:\s+(\d+)/mi', $request, $match)) {
            $this->fail("Unable to find content-length header in request");
        }

        $this->assertEquals(filesize($bodyFile), (int) $match[1]);
    }

    public function testSetDisabledAuthBeforSettingUriBug(): void
    {
        $client = new Zend_Http_Client_StaticTest_Mock();
        $client->setAuth(false);
        $this->assertTrue(true);
    }

    public function testOpenTempStreamWithValidFileDoesntThrowsException(): void
    {
        $this->markTestSkipped('Network test - requires connecting to an external host');
    }

    public function testOpenTempStreamWithBogusFileClosesTheConnection(): void
    {
        $this->markTestSkipped('Network test - requires connecting to an external host');
    }

    public function testRedirectWithTrailingSpaceInLocationHeaderZF11283(): void
    {
        $this->_client->setUri('http://example.com/');
        $this->_client->setAdapter('Zend_Http_Client_Adapter_Test');

        $adapter = $this->_client->getAdapter();

        $response = "HTTP/1.1 302 Redirect\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Location: /test\r\n"
            . "Server: Microsoft-IIS/7.0\r\n"
            . "Date: Tue, 19 Apr 2011 11:23:48 GMT\r\n\r\n"
            . "RESPONSE";

        $adapter->setResponse($response);

        $res = $this->_client->request('GET');

        $lastUri = $this->_client->getUri();

        $this->assertEquals("/test", $lastUri->getPath());
    }

    public function testClientDoesNotModifyPassedUri(): void
    {
        $uri = Zend_Uri_Http::fromString('http://example.org/');
        $orig = clone $uri;
        $client = new Zend_Http_Client($uri);
        $this->assertEquals((string)$orig, (string)$uri);
    }

    public function testStreamWarningRewind(): void
    {
        $this->markTestSkipped('Network test - requires connecting to an external host');
    }

    /**
     * Data providers
     */

    public static function validMethodProvider(): array
    {
        return array(
            array('OPTIONS'),
            array('POST'),
            array('DOSOMETHING'),
            array('PROPFIND'),
            array('Some_Characters'),
            array('X-MS-ENUMATTS')
        );
    }

    public static function invalidMethodProvider(): array
    {
        return array(
            array('N@5TYM3T#0D'),
            array('TWO WORDS'),
            array('GET http://foo.com/?'),
            array("Injected\nnewline")
        );
    }

    public static function invalidConfigProvider(): array
    {
        return array(
            array(false),
            array('foo => bar'),
            array(null),
            array(new stdClass),
            array(55)
        );
    }
}

class Zend_Http_Client_StaticTest_Mock extends Zend_Http_Client
{
    public $config = array(
        'maxredirects'    => 5,
        'strictredirects' => false,
        'useragent'       => 'Zend_Http_Client',
        'timeout'         => 10,
        'adapter'         => 'Zend_Http_Client_Adapter_Socket',
        'httpversion'     => self::HTTP_1,
        'keepalive'       => false,
        'storeresponse'   => true,
        'strict'          => true,
        'output_stream'   => false,
        'encodecookies'   => true,
    );
}

class Zend_Http_Client_StaticTest_TestAdapter_Mock extends Zend_Http_Client_Adapter_Test
{
    public $config = array();
}
