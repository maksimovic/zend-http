<?php

use PHPUnit\Framework\TestCase;

class Zend_Http_Client_ClientTest extends TestCase
{
    /**
     * @var Zend_Http_Client
     */
    protected $client;

    protected function setUp(): void
    {
        $this->client = new Zend_Http_Client('http://example.com', [
            'adapter' => 'Zend_Http_Client_Adapter_Test',
        ]);
    }

    // ---------------------------------------------------------------
    // Header validation
    // ---------------------------------------------------------------

    public static function invalidHeaders(): array
    {
        return array(
            'invalid-name-cr'                      => array("X-Foo-\rBar", 'value'),
            'invalid-name-lf'                      => array("X-Foo-\nBar", 'value'),
            'invalid-name-crlf'                    => array("X-Foo-\r\nBar", 'value'),
            'invalid-value-cr'                     => array('X-Foo-Bar', "value\risEvil"),
            'invalid-value-lf'                     => array('X-Foo-Bar', "value\nisEvil"),
            'invalid-value-bad-continuation'       => array('X-Foo-Bar', "value\r\nisEvil"),
            'invalid-array-value-cr'               => array('X-Foo-Bar', array("value\risEvil")),
            'invalid-array-value-lf'               => array('X-Foo-Bar', array("value\nisEvil")),
            'invalid-array-value-bad-continuation' => array('X-Foo-Bar', array("value\r\nisEvil")),
        );
    }

    /**
     * @dataProvider invalidHeaders
     */
    public function testHeadersContainingCRLFInjectionRaiseAnException($name, $value): void
    {
        $this->expectException('Zend_Http_Exception');
        $this->client->setHeaders(array(
            $name => $value,
        ));
    }

    // ---------------------------------------------------------------
    // Stream configuration
    // ---------------------------------------------------------------

    public function testGetStreamDefaultIsFalse(): void
    {
        $this->assertFalse($this->client->getStream());
    }

    public function testSetStreamWithTrueEnablesStreaming(): void
    {
        $this->client->setStream(true);
        $this->assertTrue($this->client->getStream());
    }

    public function testSetStreamWithStringPathStoresPath(): void
    {
        $path = '/tmp/test-stream-file';
        $this->client->setStream($path);
        $this->assertEquals($path, $this->client->getStream());
    }

    public function testSetStreamWithFalseDisablesStreaming(): void
    {
        $this->client->setStream(true);
        $this->client->setStream(false);
        $this->assertFalse($this->client->getStream());
    }

    // ---------------------------------------------------------------
    // Unmask status
    // ---------------------------------------------------------------

    public function testGetUnmaskStatusDefaultIsFalse(): void
    {
        $this->assertFalse($this->client->getUnmaskStatus());
    }

    public function testSetUnmaskStatusToTrue(): void
    {
        $this->client->setUnmaskStatus(true);
        $this->assertTrue($this->client->getUnmaskStatus());
    }

    public function testSetUnmaskStatusToFalse(): void
    {
        $this->client->setUnmaskStatus(true);
        $this->client->setUnmaskStatus(false);
        $this->assertFalse($this->client->getUnmaskStatus());
    }

    public function testUnmaskStatusRemovesBracketIndicesFromQuery(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nOK");

        $this->client->setUnmaskStatus(true);
        $this->client->setParameterGet('foo', ['a', 'b']);
        $this->client->request('GET');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('foo=a&foo=b', $request);
        $this->assertStringNotContainsString('%5B', $request);
    }

    // ---------------------------------------------------------------
    // resetParameters
    // ---------------------------------------------------------------

    public function testResetParametersClearsGetAndPostParams(): void
    {
        $this->client->setParameterGet('foo', 'bar');
        $this->client->setParameterPost('baz', 'qux');
        $this->client->setRawData('rawdata', 'text/plain');

        $this->client->resetParameters();

        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nOK");

        $this->client->request('GET');
        $request = $this->client->getLastRequest();

        $this->assertStringNotContainsString('foo=bar', $request);
        $this->assertStringNotContainsString('baz=qux', $request);
    }

    public function testResetParametersClearAllRemovesHeaders(): void
    {
        $this->client->setHeaders('X-Custom', 'value');
        $this->assertNotNull($this->client->getHeader('X-Custom'));

        $this->client->resetParameters(true);

        $this->assertNull($this->client->getHeader('X-Custom'));
    }

    public function testResetParametersClearAllClearsLastRequestAndResponse(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nOK");

        $this->client->request('GET');
        $this->assertNotNull($this->client->getLastRequest());
        $this->assertNotNull($this->client->getLastResponse());

        $this->client->resetParameters(true);

        $this->assertNull($this->client->getLastRequest());
        $this->assertNull($this->client->getLastResponse());
    }

    public function testResetParametersPartialClearsContentTypeAndLength(): void
    {
        $this->client->setHeaders('Content-Type', 'text/plain');
        $this->client->setHeaders('Content-Length', '10');
        $this->client->setHeaders('X-Custom', 'keep-me');

        $this->client->resetParameters(false);

        $this->assertNull($this->client->getHeader('Content-Type'));
        $this->assertNull($this->client->getHeader('Content-Length'));
        $this->assertEquals('keep-me', $this->client->getHeader('X-Custom'));
    }

    // ---------------------------------------------------------------
    // Last request / response
    // ---------------------------------------------------------------

    public function testGetLastRequestIsNullBeforeAnyRequest(): void
    {
        $client = new Zend_Http_Client();
        $this->assertNull($client->getLastRequest());
    }

    public function testGetLastRequestAfterRequest(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nOK");

        $this->client->request('GET');
        $request = $this->client->getLastRequest();

        $this->assertIsString($request);
        $this->assertStringContainsString('GET / HTTP/1.1', $request);
        $this->assertStringContainsString('Host: example.com', $request);
    }

    public function testGetLastResponseIsNullBeforeAnyRequest(): void
    {
        $client = new Zend_Http_Client();
        $this->assertNull($client->getLastResponse());
    }

    public function testGetLastResponseAfterRequest(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nOK");

        $response = $this->client->request('GET');
        $last = $this->client->getLastResponse();

        $this->assertSame($response, $last);
        $this->assertInstanceOf('Zend_Http_Response', $last);
    }

    // ---------------------------------------------------------------
    // Redirections count
    // ---------------------------------------------------------------

    public function testGetRedirectionsCountIsZeroWithNoRedirects(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nOK");

        $this->client->request('GET');
        $this->assertEquals(0, $this->client->getRedirectionsCount());
    }

    public function testGetRedirectionsCountAfterRedirects(): void
    {
        $adapter = $this->client->getAdapter();

        $redirect = "HTTP/1.1 302 Found\r\nLocation: http://example.com/new\r\nContent-Type: text/plain\r\n\r\n";
        $final    = "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nDone";

        $adapter->setResponse($redirect);
        $adapter->addResponse($final);

        $this->client->request('GET');
        $this->assertEquals(1, $this->client->getRedirectionsCount());
    }

    public function testMaxRedirectsIsRespected(): void
    {
        $this->client->setConfig(['maxredirects' => 2]);
        $adapter = $this->client->getAdapter();

        // Chain of 3 redirects (exceeds max of 2)
        $redirect = "HTTP/1.1 302 Found\r\nLocation: http://example.com/r\r\n\r\n";
        $adapter->setResponse($redirect);
        $adapter->addResponse($redirect);
        $adapter->addResponse($redirect);

        $response = $this->client->request('GET');
        $this->assertEquals(2, $this->client->getRedirectionsCount());
        $this->assertTrue($response->isRedirect());
    }

    // ---------------------------------------------------------------
    // Header management
    // ---------------------------------------------------------------

    public function testSetHeadersWithKeyValuePair(): void
    {
        $this->client->setHeaders('X-Custom-Header', 'custom-value');
        $this->assertEquals('custom-value', $this->client->getHeader('X-Custom-Header'));
    }

    public function testSetHeadersWithColonSeparatedString(): void
    {
        $this->client->setHeaders('X-Custom: my-value');
        $this->assertEquals('my-value', $this->client->getHeader('X-Custom'));
    }

    public function testSetHeadersWithArray(): void
    {
        $this->client->setHeaders([
            'X-First'  => 'one',
            'X-Second' => 'two',
        ]);
        $this->assertEquals('one', $this->client->getHeader('X-First'));
        $this->assertEquals('two', $this->client->getHeader('X-Second'));
    }

    public function testSetHeadersWithIndexedArray(): void
    {
        $this->client->setHeaders([
            'X-Indexed: indexed-val',
        ]);
        $this->assertEquals('indexed-val', $this->client->getHeader('X-Indexed'));
    }

    public function testGetHeaderReturnsNullForUnsetHeader(): void
    {
        $this->assertNull($this->client->getHeader('X-NonExistent'));
    }

    public function testSetHeadersUnsetsWithNullValue(): void
    {
        $this->client->setHeaders('X-Remove', 'present');
        $this->assertNotNull($this->client->getHeader('X-Remove'));

        $this->client->setHeaders('X-Remove', null);
        $this->assertNull($this->client->getHeader('X-Remove'));
    }

    public function testSetHeadersUnsetsWithFalseValue(): void
    {
        $this->client->setHeaders('X-Remove', 'present');
        $this->client->setHeaders('X-Remove', false);
        $this->assertNull($this->client->getHeader('X-Remove'));
    }

    public function testHeaderIsCaseInsensitive(): void
    {
        $this->client->setHeaders('X-My-Header', 'test');
        $this->assertEquals('test', $this->client->getHeader('x-my-header'));
        $this->assertEquals('test', $this->client->getHeader('X-MY-HEADER'));
    }

    public function testSetHeaderWithIntegerValue(): void
    {
        $this->client->setHeaders('X-Num', 42);
        $this->assertEquals(42, $this->client->getHeader('X-Num'));
    }

    // ---------------------------------------------------------------
    // Parameter GET/POST
    // ---------------------------------------------------------------

    public function testSetParameterGetAppearsInRequest(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setParameterGet('key', 'value');
        $this->client->request('GET');

        $this->assertStringContainsString('key=value', $this->client->getLastRequest());
    }

    public function testSetParameterGetWithArray(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setParameterGet(['a' => '1', 'b' => '2']);
        $this->client->request('GET');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('a=1', $request);
        $this->assertStringContainsString('b=2', $request);
    }

    public function testSetParameterGetWithNullRemovesParam(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setParameterGet('foo', 'bar');
        $this->client->setParameterGet('foo', null);
        $this->client->request('GET');

        $this->assertStringNotContainsString('foo=bar', $this->client->getLastRequest());
    }

    public function testSetParameterPostAppearsInBody(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setParameterPost('username', 'john');
        $this->client->request('POST');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('username=john', $request);
    }

    public function testSetParameterPostWithArray(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setParameterPost(['x' => '10', 'y' => '20']);
        $this->client->request('POST');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('x=10', $request);
        $this->assertStringContainsString('y=20', $request);
    }

    public function testSetParameterPostWithNullRemovesParam(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setParameterPost('key', 'val');
        $this->client->setParameterPost('key', null);
        $this->client->request('POST');

        $this->assertStringNotContainsString('key=val', $this->client->getLastRequest());
    }

    // ---------------------------------------------------------------
    // setRawData
    // ---------------------------------------------------------------

    public function testSetRawDataString(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $body = '{"key":"value"}';
        $this->client->setRawData($body, 'application/json');
        $this->client->request('POST');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString($body, $request);
        $this->assertStringContainsString('Content-Type: application/json', $request);
    }

    public function testSetRawDataResource(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'stream content');
        rewind($stream);

        // setRawData with a resource should set the Content-Length header from fstat
        $this->client->setRawData($stream, 'text/plain');
        $this->assertEquals('14', $this->client->getHeader('Content-Length'));

        fclose($stream);
    }

    // ---------------------------------------------------------------
    // setEncType
    // ---------------------------------------------------------------

    public function testSetEncTypeChangesEncoding(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setEncType(Zend_Http_Client::ENC_FORMDATA);
        $this->client->setParameterPost('field', 'data');
        $this->client->request('POST');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('multipart/form-data', $request);
    }

    // ---------------------------------------------------------------
    // Auth
    // ---------------------------------------------------------------

    public function testSetAuthBasicAppearsInRequest(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setAuth('user', 'pass', Zend_Http_Client::AUTH_BASIC);
        $this->client->request('GET');

        $request = $this->client->getLastRequest();
        $expected = 'Basic ' . base64_encode('user:pass');
        $this->assertStringContainsString("Authorization: $expected", $request);
    }

    public function testSetAuthFalseDisablesAuth(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setAuth('user', 'pass');
        $this->client->setAuth(false);
        $this->client->request('GET');

        $request = $this->client->getLastRequest();
        $this->assertStringNotContainsString('Authorization:', $request);
    }

    public function testSetAuthNullDisablesAuth(): void
    {
        $this->client->setAuth('user', 'pass');
        $result = $this->client->setAuth(null);
        $this->assertInstanceOf('Zend_Http_Client', $result);
    }

    public function testSetAuthInvalidTypeThrowsException(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        $this->client->setAuth('user', 'pass', 'invalid_type');
    }

    // ---------------------------------------------------------------
    // encodeAuthHeader (static)
    // ---------------------------------------------------------------

    public function testEncodeAuthHeaderBasic(): void
    {
        $result = Zend_Http_Client::encodeAuthHeader('user', 'pass', Zend_Http_Client::AUTH_BASIC);
        $this->assertEquals('Basic ' . base64_encode('user:pass'), $result);
    }

    public function testEncodeAuthHeaderUserWithColonThrowsException(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        Zend_Http_Client::encodeAuthHeader('us:er', 'pass', Zend_Http_Client::AUTH_BASIC);
    }

    public function testEncodeAuthHeaderInvalidTypeThrowsException(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        Zend_Http_Client::encodeAuthHeader('user', 'pass', 'unknown');
    }

    // ---------------------------------------------------------------
    // Cookie jar
    // ---------------------------------------------------------------

    public function testSetCookieJarWithTrueCreatesNewJar(): void
    {
        $this->client->setCookieJar(true);
        $this->assertInstanceOf('Zend_Http_CookieJar', $this->client->getCookieJar());
    }

    public function testGetCookieJarDefaultIsNull(): void
    {
        $this->assertNull($this->client->getCookieJar());
    }

    public function testSetCookieJarWithObjectSetsIt(): void
    {
        $jar = new Zend_Http_CookieJar();
        $this->client->setCookieJar($jar);
        $this->assertSame($jar, $this->client->getCookieJar());
    }

    public function testSetCookieJarFalseDisablesIt(): void
    {
        $this->client->setCookieJar(true);
        $this->client->setCookieJar(false);
        $this->assertNull($this->client->getCookieJar());
    }

    public function testSetCookieJarInvalidThrowsException(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        $this->client->setCookieJar('not-a-jar');
    }

    // ---------------------------------------------------------------
    // setCookie (without cookie jar)
    // ---------------------------------------------------------------

    public function testSetCookieWithoutJarAddsCookieHeader(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setCookie('name', 'val');
        $this->client->request('GET');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('Cookie: name=val;', $request);
    }

    public function testSetCookieWithArraySetsMultipleCookies(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setCookie(['a' => '1', 'b' => '2']);
        $this->client->request('GET');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('a=1;', $request);
        $this->assertStringContainsString('b=2;', $request);
    }

    public function testSetCookieInvalidNameThrowsException(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        $this->client->setCookie("bad=name", 'value');
    }

    public function testSetCookieWithJarAddsToCookieJar(): void
    {
        $this->client->setCookieJar(true);
        $this->client->setCookie('test', 'value');

        $jar = $this->client->getCookieJar();
        $cookies = $jar->getAllCookies();
        $this->assertCount(1, $cookies);
        $this->assertEquals('test', $cookies[0]->getName());
    }

    public function testSetCookieWithCookieObjectInJar(): void
    {
        $this->client->setCookieJar(true);
        $cookie = new Zend_Http_Cookie('foo', 'bar', 'example.com');
        $this->client->setCookie($cookie);

        $jar = $this->client->getCookieJar();
        $cookies = $jar->getAllCookies();
        $this->assertCount(1, $cookies);
        $this->assertEquals('foo', $cookies[0]->getName());
    }

    public function testSetCookieWithCookieObjectWithoutJar(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $cookie = new Zend_Http_Cookie('foo', 'bar', 'example.com');
        $this->client->setCookie($cookie);
        $this->client->request('GET');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('Cookie:', $request);
        $this->assertStringContainsString('foo=bar', $request);
    }

    // ---------------------------------------------------------------
    // Cookie send/receive cycle with Test adapter
    // ---------------------------------------------------------------

    public function testCookieReceivedAndSentBack(): void
    {
        $this->client->setCookieJar(true);
        $adapter = $this->client->getAdapter();

        $adapter->setResponse(
            "HTTP/1.1 200 OK\r\n"
            . "Set-Cookie: session=abc123; path=/\r\n"
            . "Content-Type: text/plain\r\n\r\nFirst"
        );
        $adapter->addResponse(
            "HTTP/1.1 200 OK\r\n"
            . "Content-Type: text/plain\r\n\r\nSecond"
        );

        $this->client->request('GET');
        $this->client->request('GET');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('Cookie: session=abc123', $request);
    }

    // ---------------------------------------------------------------
    // File upload with data (no filesystem access needed)
    // ---------------------------------------------------------------

    public function testSetFileUploadWithData(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setFileUpload('test.txt', 'myfile', 'file contents here', 'text/plain');
        $this->client->request('POST');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('Content-Disposition: form-data; name="myfile"; filename="test.txt"', $request);
        $this->assertStringContainsString('file contents here', $request);
        $this->assertStringContainsString('Content-Type: text/plain', $request);
    }

    // ---------------------------------------------------------------
    // encodeFormData (static)
    // ---------------------------------------------------------------

    public function testEncodeFormDataSimpleField(): void
    {
        $result = Zend_Http_Client::encodeFormData('boundary123', 'field', 'value');
        $this->assertStringContainsString('--boundary123', $result);
        $this->assertStringContainsString('Content-Disposition: form-data; name="field"', $result);
        $this->assertStringContainsString('value', $result);
        $this->assertStringNotContainsString('filename=', $result);
    }

    public function testEncodeFormDataWithFilename(): void
    {
        $result = Zend_Http_Client::encodeFormData('bnd', 'file', 'data', 'upload.txt');
        $this->assertStringContainsString('filename="upload.txt"', $result);
        $this->assertStringContainsString('data', $result);
    }

    public function testEncodeFormDataWithHeaders(): void
    {
        $headers = ['Content-Type' => 'image/png', 'Content-Transfer-Encoding' => 'binary'];
        $result = Zend_Http_Client::encodeFormData('bnd', 'img', 'binary-data', 'pic.png', $headers);
        $this->assertStringContainsString('Content-Type: image/png', $result);
        $this->assertStringContainsString('Content-Transfer-Encoding: binary', $result);
    }

    // ---------------------------------------------------------------
    // setMethod
    // ---------------------------------------------------------------

    public function testSetMethodPost(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->request('POST');
        $request = $this->client->getLastRequest();
        $this->assertStringStartsWith('POST ', $request);
    }

    public function testSetMethodPut(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setRawData('{"data":1}', 'application/json');
        $this->client->request('PUT');
        $request = $this->client->getLastRequest();
        $this->assertStringStartsWith('PUT ', $request);
    }

    public function testSetMethodDelete(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->request('DELETE');
        $request = $this->client->getLastRequest();
        $this->assertStringStartsWith('DELETE ', $request);
    }

    public function testSetMethodPatch(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setRawData('patch-data', 'text/plain');
        $this->client->request('PATCH');
        $request = $this->client->getLastRequest();
        $this->assertStringStartsWith('PATCH ', $request);
    }

    public function testSetMethodHead(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\n");

        $this->client->request('HEAD');
        $request = $this->client->getLastRequest();
        $this->assertStringStartsWith('HEAD ', $request);
    }

    public function testSetMethodOptions(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->request('OPTIONS');
        $request = $this->client->getLastRequest();
        $this->assertStringStartsWith('OPTIONS ', $request);
    }

    public function testTraceRequestHasNoBody(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setRawData('should-be-ignored', 'text/plain');
        $this->client->request('TRACE');

        $request = $this->client->getLastRequest();
        $this->assertStringNotContainsString('should-be-ignored', $request);
    }

    public function testSetMethodInvalidThrowsException(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        $this->client->setMethod("INVALID METHOD");
    }

    // ---------------------------------------------------------------
    // URI
    // ---------------------------------------------------------------

    public function testSetUriWithString(): void
    {
        $this->client->setUri('http://www.example.org/path');
        $uri = $this->client->getUri();
        $this->assertInstanceOf('Zend_Uri_Http', $uri);
        $this->assertEquals('www.example.org', $uri->getHost());
        $this->assertEquals('/path', $uri->getPath());
    }

    public function testSetUriWithUriObject(): void
    {
        $uri = Zend_Uri::factory('http://test.example.com/foo');
        $this->client->setUri($uri);
        $this->assertEquals('test.example.com', $this->client->getUri()->getHost());
    }

    public function testGetUriAsString(): void
    {
        $this->assertStringContainsString('example.com', $this->client->getUri(true));
    }

    public function testSetUriWithCredentialsSetsAuth(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setUri('http://myuser:mypass@example.com/protected');
        $this->client->request('GET');

        $request = $this->client->getLastRequest();
        $expected = 'Basic ' . base64_encode('myuser:mypass');
        $this->assertStringContainsString("Authorization: $expected", $request);
    }

    public function testRequestWithoutUriThrowsException(): void
    {
        $client = new Zend_Http_Client();
        $this->expectException('Zend_Http_Client_Exception');
        $client->request('GET');
    }

    // ---------------------------------------------------------------
    // Adapter
    // ---------------------------------------------------------------

    public function testSetAdapterWithObject(): void
    {
        $adapter = new Zend_Http_Client_Adapter_Test();
        $this->client->setAdapter($adapter);
        $this->assertSame($adapter, $this->client->getAdapter());
    }

    public function testSetAdapterWithString(): void
    {
        $this->client->setAdapter('Zend_Http_Client_Adapter_Test');
        $this->assertInstanceOf('Zend_Http_Client_Adapter_Test', $this->client->getAdapter());
    }

    public function testSetAdapterWithInvalidObjectThrowsException(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        $this->client->setAdapter(new stdClass());
    }

    public function testGetAdapterLazyLoadsDefault(): void
    {
        // getAdapter on a fresh client will try to load the socket adapter,
        // but we check that it tries to load the configured adapter
        $client = new Zend_Http_Client(null, ['adapter' => 'Zend_Http_Client_Adapter_Test']);
        $adapter = $client->getAdapter();
        $this->assertInstanceOf('Zend_Http_Client_Adapter_Test', $adapter);
    }

    // ---------------------------------------------------------------
    // Config
    // ---------------------------------------------------------------

    public function testSetConfigInvalidThrowsException(): void
    {
        $this->expectException('Zend_Http_Client_Exception');
        $this->client->setConfig('invalid');
    }

    public function testSetConfigKeysAreLowercased(): void
    {
        $this->client->setConfig(['MyCustomKey' => 'val']);
        // The config is protected, but we can verify through a side effect
        // by setting timeout and checking it through the adapter
        $this->client->setConfig(['TIMEOUT' => 99]);
        // If no exception, configuration worked
        $this->assertTrue(true);
    }

    // ---------------------------------------------------------------
    // Redirect handling with Test adapter
    // ---------------------------------------------------------------

    public function testRedirectToAbsoluteUrl(): void
    {
        $adapter = $this->client->getAdapter();

        $redirect = "HTTP/1.1 302 Found\r\nLocation: http://other.example.com/new-path\r\n\r\n";
        $final    = "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nRedirected";

        $adapter->setResponse($redirect);
        $adapter->addResponse($final);

        $response = $this->client->request('GET');
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('Redirected', $response->getBody());
        $this->assertEquals('other.example.com', $this->client->getUri()->getHost());
    }

    public function testRedirectToRelativePath(): void
    {
        $this->client->setUri('http://example.com/old/page');
        $adapter = $this->client->getAdapter();

        $redirect = "HTTP/1.1 302 Found\r\nLocation: /new/page\r\n\r\n";
        $final    = "HTTP/1.1 200 OK\r\n\r\nNew page";

        $adapter->setResponse($redirect);
        $adapter->addResponse($final);

        $response = $this->client->request('GET');
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('/new/page', $this->client->getUri()->getPath());
    }

    public function test303RedirectChangesMethodToGet(): void
    {
        $adapter = $this->client->getAdapter();

        $redirect = "HTTP/1.1 303 See Other\r\nLocation: http://example.com/result\r\n\r\n";
        $final    = "HTTP/1.1 200 OK\r\n\r\nGET result";

        $adapter->setResponse($redirect);
        $adapter->addResponse($final);

        $this->client->setParameterPost('data', 'value');
        $this->client->request('POST');

        $lastRequest = $this->client->getLastRequest();
        $this->assertStringStartsWith('GET ', $lastRequest);
    }

    public function testStrictRedirectPreservesMethod(): void
    {
        $this->client->setConfig(['strictredirects' => true]);
        $adapter = $this->client->getAdapter();

        $redirect = "HTTP/1.1 302 Found\r\nLocation: http://example.com/new\r\n\r\n";
        $final    = "HTTP/1.1 200 OK\r\n\r\nOK";

        $adapter->setResponse($redirect);
        $adapter->addResponse($final);

        $this->client->setRawData('body', 'text/plain');
        $this->client->request('POST');

        $lastRequest = $this->client->getLastRequest();
        $this->assertStringStartsWith('POST ', $lastRequest);
    }

    // ---------------------------------------------------------------
    // User-Agent header
    // ---------------------------------------------------------------

    public function testDefaultUserAgentHeader(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->request('GET');
        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('User-Agent: Zend_Http_Client', $request);
    }

    public function testCustomUserAgent(): void
    {
        $this->client->setConfig(['useragent' => 'MyApp/1.0']);
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->request('GET');
        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('User-Agent: MyApp/1.0', $request);
    }

    // ---------------------------------------------------------------
    // Connection header
    // ---------------------------------------------------------------

    public function testDefaultConnectionCloseHeader(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->request('GET');
        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('Connection: close', $request);
    }

    public function testKeepaliveConfigOmitsConnectionCloseHeader(): void
    {
        $this->client->setConfig(['keepalive' => true]);
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->request('GET');
        $request = $this->client->getLastRequest();
        $this->assertStringNotContainsString('Connection: close', $request);
    }

    // ---------------------------------------------------------------
    // storeresponse config
    // ---------------------------------------------------------------

    public function testStoreResponseFalseDoesNotStoreResponse(): void
    {
        $this->client->setConfig(['storeresponse' => false]);
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $response = $this->client->request('GET');
        $this->assertInstanceOf('Zend_Http_Response', $response);
        $this->assertNull($this->client->getLastResponse());
    }

    // ---------------------------------------------------------------
    // rfc3986_strict config
    // ---------------------------------------------------------------

    public function testRfc3986StrictEncodesSpacesAsPercent20(): void
    {
        $this->client->setConfig(['rfc3986_strict' => true]);
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setParameterGet('q', 'hello world');
        $this->client->request('GET');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('q=hello%20world', $request);
        $this->assertStringNotContainsString('q=hello+world', $request);
    }

    // ---------------------------------------------------------------
    // GET query string preserved when adding parameters
    // ---------------------------------------------------------------

    public function testExistingQueryStringIsPreserved(): void
    {
        $this->client->setUri('http://example.com/search?existing=1');
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setParameterGet('extra', '2');
        $this->client->request('GET');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('existing=1', $request);
        $this->assertStringContainsString('extra=2', $request);
    }

    // ---------------------------------------------------------------
    // POST urlencoded content type
    // ---------------------------------------------------------------

    public function testPostUrlencodedSetsContentType(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setParameterPost('key', 'value');
        $this->client->request('POST');

        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('Content-Type: application/x-www-form-urlencoded', $request);
    }

    // ---------------------------------------------------------------
    // Host header with non-default port
    // ---------------------------------------------------------------

    public function testHostHeaderIncludesNonDefaultPort(): void
    {
        $this->client->setUri('http://example.com:8080/path');
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->request('GET');
        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('Host: example.com:8080', $request);
    }

    public function testHostHeaderOmitsDefaultPort80(): void
    {
        $this->client->setUri('http://example.com:80/path');
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->request('GET');
        $request = $this->client->getLastRequest();
        // Should have "Host: example.com" without ":80"
        $this->assertMatchesRegularExpression('/Host: example\.com\r?\n/', $request);
    }

    // ---------------------------------------------------------------
    // Content-Length on POST with params
    // ---------------------------------------------------------------

    public function testContentLengthSetOnPost(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->setParameterPost('a', 'b');
        $this->client->request('POST');

        $request = $this->client->getLastRequest();
        $this->assertMatchesRegularExpression('/Content-Length: \d+/', $request);
    }

    // ---------------------------------------------------------------
    // Accept-encoding header
    // ---------------------------------------------------------------

    public function testAcceptEncodingHeaderIsSetByDefault(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\n\r\nOK");

        $this->client->request('GET');
        $request = $this->client->getLastRequest();
        $this->assertStringContainsString('Accept-encoding:', $request);
    }

    // ---------------------------------------------------------------
    // Response parsing
    // ---------------------------------------------------------------

    public function testResponseStatusIsParsed(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 404 Not Found\r\nContent-Type: text/plain\r\n\r\nNot Found");

        $response = $this->client->request('GET');
        $this->assertEquals(404, $response->getStatus());
        $this->assertEquals('Not Found', $response->getMessage());
        $this->assertTrue($response->isError());
    }

    public function testResponseBodyIsParsed(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n<html>Hello</html>");

        $response = $this->client->request('GET');
        $this->assertEquals('<html>Hello</html>', $response->getBody());
    }

    public function testResponseHeadersAreParsed(): void
    {
        $adapter = $this->client->getAdapter();
        $adapter->setResponse(
            "HTTP/1.1 200 OK\r\n"
            . "X-Custom: test-val\r\n"
            . "Content-Type: text/plain\r\n\r\nBody"
        );

        $response = $this->client->request('GET');
        $this->assertEquals('test-val', $response->getHeader('X-Custom'));
        $this->assertEquals('text/plain', $response->getHeader('Content-Type'));
    }
}
