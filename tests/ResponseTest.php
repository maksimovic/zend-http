<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/StreamObject.php';

class Zend_Http_ResponseTest extends TestCase
{
    /** @var null|string */
    private $tempFile;

    protected function setUp(): void
    { }

    protected function tearDown(): void
    {
        if ($this->tempFile !== null && file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testGzipResponse(): void
    {
        $response_text = file_get_contents(dirname(__FILE__) . '/_files/response_gzip');

        $res = Zend_Http_Response::fromString($response_text);

        $this->assertEquals('gzip', $res->getHeader('Content-encoding'));
        $this->assertEquals('0b13cb193de9450aa70a6403e2c9902f', md5($res->getBody()));
        $this->assertEquals('f24dd075ba2ebfb3bf21270e3fdc5303', md5($res->getRawBody()));
    }

    public function testDeflateResponse(): void
    {
        $response_text = file_get_contents(dirname(__FILE__) . '/_files/response_deflate');

        $res = Zend_Http_Response::fromString($response_text);

        $this->assertEquals('deflate', $res->getHeader('Content-encoding'));
        $this->assertEquals('0b13cb193de9450aa70a6403e2c9902f', md5($res->getBody()));
        $this->assertEquals('ad62c21c3aa77b6a6f39600f6dd553b8', md5($res->getRawBody()));
    }

    public function testNonStandardDeflateResponseZF6040(): void
    {
        $response_text = file_get_contents(dirname(__FILE__) . '/_files/response_deflate_iis');

        list($headers, $message) = explode("\n\n", $response_text, 2);
        $headers = preg_replace("#(?<!\r)\n#", "\r\n", $headers);
        $response_text = $headers . "\r\n\r\n" . $message;

        $res = Zend_Http_Response::fromString($response_text);

        $this->assertEquals('deflate', $res->getHeader('Content-encoding'));
        $this->assertEquals('d82c87e3d5888db0193a3fb12396e616', md5($res->getBody()));
        $this->assertEquals('c830dd74bb502443cf12514c185ff174', md5($res->getRawBody()));
    }

    public function testChunkedResponse(): void
    {
        $response_text = file_get_contents(dirname(__FILE__) . '/_files/response_chunked');

        $res = Zend_Http_Response::fromString($response_text);

        $this->assertEquals('chunked', $res->getHeader('Transfer-encoding'));
        $this->assertEquals('0b13cb193de9450aa70a6403e2c9902f', md5($res->getBody()));
        $this->assertEquals('c0cc9d44790fa2a58078059bab1902a9', md5($res->getRawBody()));
    }

    public function testChunkedResponseCaseInsensitiveZF5438(): void
    {
        $response_text = file_get_contents(dirname(__FILE__) . '/_files/response_chunked_case');

        $res = Zend_Http_Response::fromString($response_text);

        $this->assertEquals('chunked', strtolower($res->getHeader('Transfer-encoding')));
        $this->assertEquals('0b13cb193de9450aa70a6403e2c9902f', md5($res->getBody()));
        $this->assertEquals('c0cc9d44790fa2a58078059bab1902a9', md5($res->getRawBody()));
    }

    public function testExtractMessageCrlf(): void
    {
        $response_text = file_get_contents(dirname(__FILE__) . '/_files/response_crlf');
        $this->assertEquals("OK", Zend_Http_Response::extractMessage($response_text), "Response message is not 'OK' as expected");
    }

    public function testExtractMessageLfonly(): void
    {
        $response_text = file_get_contents(dirname(__FILE__) . '/_files/response_lfonly');
        $this->assertEquals("OK", Zend_Http_Response::extractMessage($response_text), "Response message is not 'OK' as expected");
    }

    public function test404IsError(): void
    {
        $response_text = $this->readResponse('response_404');
        $response = Zend_Http_Response::fromString($response_text);

        $this->assertEquals(404, $response->getStatus(), 'Response code is expected to be 404, but it\'s not.');
        $this->assertTrue($response->isError(), 'Response is an error, but isError() returned false');
        $this->assertFalse($response->isSuccessful(), 'Response is an error, but isSuccessful() returned true');
        $this->assertFalse($response->isRedirect(), 'Response is an error, but isRedirect() returned true');
    }

    public function test500isError(): void
    {
        $response_text = $this->readResponse('response_500');
        $response = Zend_Http_Response::fromString($response_text);

        $this->assertEquals(500, $response->getStatus(), 'Response code is expected to be 500, but it\'s not.');
        $this->assertTrue($response->isError(), 'Response is an error, but isError() returned false');
        $this->assertFalse($response->isSuccessful(), 'Response is an error, but isSuccessful() returned true');
        $this->assertFalse($response->isRedirect(), 'Response is an error, but isRedirect() returned true');
    }

    public function test302LocationHeaderMatches(): void
    {
        $headerName  = 'Location';
        $headerValue = 'http://www.google.com/ig?hl=en';
        $response    = Zend_Http_Response::fromString($this->readResponse('response_302'));
        $responseIis = Zend_Http_Response::fromString($this->readResponse('response_302_iis'));

        $this->assertEquals($headerValue, $response->getHeader($headerName));
        $this->assertEquals($headerValue, $responseIis->getHeader($headerName));
    }

    public function test300isRedirect(): void
    {
        $response = Zend_Http_Response::fromString($this->readResponse('response_302'));

        $this->assertEquals(302, $response->getStatus(), 'Response code is expected to be 302, but it\'s not.');
        $this->assertTrue($response->isRedirect(), 'Response is a redirection, but isRedirect() returned false');
        $this->assertFalse($response->isError(), 'Response is a redirection, but isError() returned true');
        $this->assertFalse($response->isSuccessful(), 'Response is a redirection, but isSuccessful() returned true');
    }

    public function testDestructionDoesNothingIfStreamIsNotAResourceAndStreamNameIsNotAString(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'lhrs');
        $streamObject = new \Zend\Http\StreamObject($this->tempFile);

        $response = new Zend_Http_Response_Stream(200, array());
        $response->setCleanup(true);
        $response->setStreamName($streamObject);

        unset($response);

        $this->assertFileExists($this->tempFile);
    }

    public function test200Ok(): void
    {
        $response = Zend_Http_Response::fromString($this->readResponse('response_deflate'));

        $this->assertEquals(200, $response->getStatus(), 'Response code is expected to be 200, but it\'s not.');
        $this->assertFalse($response->isError(), 'Response is OK, but isError() returned true');
        $this->assertTrue($response->isSuccessful(), 'Response is OK, but isSuccessful() returned false');
        $this->assertFalse($response->isRedirect(), 'Response is OK, but isRedirect() returned true');
    }

    public function test100Continue(): void
    {
        $this->markTestIncomplete();
    }

    public function testAutoMessageSet(): void
    {
        $response = Zend_Http_Response::fromString($this->readResponse('response_403_nomessage'));

        $this->assertEquals(403, $response->getStatus(), 'Response status is expected to be 403, but it isn\'t');
        $this->assertEquals('Forbidden', $response->getMessage(), 'Response is 403, but message is not "Forbidden" as expected');

        $this->assertTrue($response->isError(), 'Response is an error, but isError() returned false');
        $this->assertFalse($response->isSuccessful(), 'Response is an error, but isSuccessful() returned true');
        $this->assertFalse($response->isRedirect(), 'Response is an error, but isRedirect() returned true');
    }

    public function testAsString(): void
    {
        $response_str = $this->readResponse('response_404');
        $response = Zend_Http_Response::fromString($response_str);

        $this->assertEquals(strtolower($response_str), strtolower($response->asString()), 'Response conversion to string does not match original string');
        $this->assertEquals(strtolower($response_str), strtolower((string) $response), 'Response conversion to string does not match original string');
    }

    public function testGetHeaders(): void
    {
        $response = Zend_Http_Response::fromString($this->readResponse('response_deflate'));
        $headers = $response->getHeaders();

        $this->assertEquals(8, count($headers), 'Header count is not as expected');
        $this->assertEquals('Apache', $headers['Server'], 'Server header is not as expected');
        $this->assertEquals('deflate', $headers['Content-encoding'], 'Content-type header is not as expected');
    }

    public function testGetVersion(): void
    {
        $response = Zend_Http_Response::fromString($this->readResponse('response_chunked'));
        $this->assertEquals(1.1, $response->getVersion(), 'Version is expected to be 1.1');
    }

    public function testResponseCodeAsText(): void
    {
        $this->assertEquals('Continue', Zend_Http_Response::responseCodeAsText(100));
        $this->assertEquals('OK', Zend_Http_Response::responseCodeAsText(200));
        $this->assertEquals('Multiple Choices', Zend_Http_Response::responseCodeAsText(300));
        $this->assertEquals('Bad Request', Zend_Http_Response::responseCodeAsText(400));
        $this->assertEquals('Internal Server Error', Zend_Http_Response::responseCodeAsText(500));

        $this->assertEquals('Unknown', Zend_Http_Response::responseCodeAsText(600));

        $this->assertEquals('Found', Zend_Http_Response::responseCodeAsText(302));
        $this->assertEquals('Moved Temporarily', Zend_Http_Response::responseCodeAsText(302, false));

        $codes = Zend_Http_Response::responseCodeAsText();
        $this->assertIsArray($codes);
        $this->assertEquals('OK', $codes[200]);
    }

    public function testUnknownCode(): void
    {
        $response_str = $this->readResponse('response_unknown');
        $response = Zend_Http_Response::fromString($response_str);

        $this->assertEquals(550, $response->getStatus(), 'Status is expected to be a non-standard 550');
        $this->assertEquals('Printer On Fire', $response->getMessage(), 'Message is expected to be extracted');

        $this->assertEquals('Unknown', Zend_Http_Response::responseCodeAsText($response_str));
    }

    public function testMultilineHeader(): void
    {
        $response = Zend_Http_Response::fromString($this->readResponse('response_multiline_header'));

        $this->assertEquals(6, count($response->getHeaders()), 'Header count is expected to be 6');

        $this->assertEquals('timeout=15, max=100', $response->getHeader('keep-alive'));
        $this->assertEquals('text/html; charset=iso-8859-1', $response->getHeader('content-type'));
    }

    public function testExceptInvalidChunkedBody(): void
    {
        try {
            Zend_Http_Response::decodeChunkedBody($this->readResponse('response_deflate'));
            $this->fail('An expected exception was not thrown');
        } catch (Zend_Http_Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testExtractorsOnInvalidString(): void
    {
        $response_str = '';

        $this->assertTrue(Zend_Http_Response::extractCode($response_str) === false);
        $this->assertTrue(Zend_Http_Response::extractMessage($response_str) === false);
        $this->assertTrue(Zend_Http_Response::extractVersion($response_str) === false);
        $this->assertTrue(Zend_Http_Response::extractBody($response_str) === '');
        $this->assertTrue(Zend_Http_Response::extractHeaders($response_str) === array());
    }

    public function testLeadingWhitespaceBody(): void
    {
        $message = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'response_leadingws');
        $body    = Zend_Http_Response::extractBody($message);
        $this->assertEquals($body, "\r\n\t  \n\r\tx", 'Extracted body is not identical to expected body');
    }

    public function testMultibyteChunkedResponse(): void
    {
        $md5 = 'f734924685f92b243c8580848cadc560';

        $response = Zend_Http_Response::fromString($this->readResponse('response_multibyte_body'));
        $this->assertEquals($md5, md5($response->getBody()));
    }

    public function testConstructorWithHeadersAssocArray(): void
    {
        $response = new Zend_Http_Response(200, array(
            'content-type' => 'text/plain',
            'x-foo'        => 'bar:baz'
        ));

        $this->assertEquals('text/plain', $response->getHeader('content-type'));
        $this->assertEquals('bar:baz', $response->getHeader('x-foo'));
    }

    public function testConstructorWithHeadersIndexedArrayZF10277(): void
    {
        $response = new Zend_Http_Response(200, array(
            'content-type: text/plain',
            'x-foo: bar:baz'
        ));

        $this->assertEquals('text/plain', $response->getHeader('content-type'));
        $this->assertEquals('bar:baz', $response->getHeader('x-foo'));
    }

    public function testConstructorWithHeadersIndexedArrayNoWhitespace(): void
    {
        $response = new Zend_Http_Response(200, array(
            'content-type:text/plain',
            'x-foo:bar:baz'
        ));

        $this->assertEquals('text/plain', $response->getHeader('content-type'));
        $this->assertEquals('bar:baz', $response->getHeader('x-foo'));
    }

    protected function readResponse($response): string
    {
        $message = file_get_contents(
            dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $response
        );
        return preg_replace("#(?<!\r)\n#", "\r\n", $message);
    }

    public static function invalidResponseHeaders(): array
    {
        return array(
            'bad-status-line'            => array("HTTP/1.0a 200 OK\r\nHost: example.com\r\n\r\nMessage Body"),
            'nl-in-header'               => array("HTTP/1.1 200 OK\r\nHost: example.\ncom\r\n\r\nMessage Body"),
            'cr-in-header'               => array("HTTP/1.1 200 OK\r\nHost: example.\rcom\r\n\r\nMessage Body"),
            'bad-continuation'           => array("HTTP/1.1 200 OK\r\nHost: example.\r\ncom\r\n\r\nMessage Body"),
            'no-status-nl-in-header'     => array("Host: example.\ncom\r\n\r\nMessage Body"),
            'no-status-cr-in-header'     => array("Host: example.\rcom\r\n\r\nMessage Body"),
            'no-status-bad-continuation' => array("Host: example.\r\ncom\r\n\r\nMessage Body"),
        );
    }

    /**
     * @dataProvider invalidResponseHeaders
     */
    public function testExtractHeadersRaisesExceptionWhenDetectingCRLFInjection($message): void
    {
        $this->expectException('Zend_Http_Exception');
        $this->expectExceptionMessage('Invalid');
        Zend_Http_Response::extractHeaders($message);
    }

    public function testExtractHeadersShouldAllowAnyValidHttpHeaderToken(): void
    {
        $response = $this->readResponse('response_587');
        $headers  = Zend_Http_Response::extractHeaders($response);

        $this->assertArrayHasKey('zipi.step', $headers);
        $this->assertEquals(0, $headers['zipi.step']);
    }

    public function testExtractHeadersShouldAllowHeadersWithEmptyValues(): void
    {
        $response = $this->readResponse('response_587_empty');
        $headers  = Zend_Http_Response::extractHeaders($response);

        $this->assertArrayHasKey('imagetoolbar', $headers);
        $this->assertEmpty($headers['imagetoolbar']);
    }

    public function testExtractHeadersShouldAllowHeadersWithMissingValues(): void
    {
        $response = $this->readResponse('response_587_null');
        $headers  = Zend_Http_Response::extractHeaders($response);

        $this->assertArrayHasKey('imagetoolbar', $headers);
        $this->assertEmpty($headers['imagetoolbar']);
    }
}
