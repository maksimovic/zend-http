<?php

use PHPUnit\Framework\TestCase;

class Zend_Http_ResponseStreamTest extends TestCase
{
    /** @var resource[] */
    private array $streamsToClose = [];

    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->streamsToClose as $stream) {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    // ---------------------------------------------------------------
    // Constructor
    // ---------------------------------------------------------------

    public function testConstructorWithStringBody(): void
    {
        $response = new Zend_Http_Response_Stream(200, ['Content-Type' => 'text/plain'], 'body text');
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('body text', $response->getBody());
    }

    public function testConstructorWithResourceBody(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'stream content');
        rewind($stream);

        $response = new Zend_Http_Response_Stream(200, ['Content-Type' => 'text/plain'], $stream);

        // Body should be readable from the stream
        $this->assertEquals('stream content', $response->getBody());
    }

    public function testConstructorWithVersionAndMessage(): void
    {
        $response = new Zend_Http_Response_Stream(404, [], null, '1.0', 'Not Found');
        $this->assertEquals(404, $response->getStatus());
        $this->assertEquals('1.0', $response->getVersion());
        $this->assertEquals('Not Found', $response->getMessage());
    }

    // ---------------------------------------------------------------
    // getStream / setStream
    // ---------------------------------------------------------------

    public function testGetStreamReturnsNull(): void
    {
        $response = new Zend_Http_Response_Stream(200, []);
        $this->assertNull($response->getStream());
    }

    public function testSetStreamAndGetStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        $this->streamsToClose[] = $stream;

        $response = new Zend_Http_Response_Stream(200, []);
        $result = $response->setStream($stream);

        $this->assertSame($stream, $response->getStream());
        $this->assertSame($response, $result, 'setStream should return $this for fluent interface');
    }

    // ---------------------------------------------------------------
    // getStreamName / setStreamName
    // ---------------------------------------------------------------

    public function testGetStreamNameReturnsNullByDefault(): void
    {
        $response = new Zend_Http_Response_Stream(200, []);
        $this->assertNull($response->getStreamName());
    }

    public function testSetStreamNameAndGetStreamName(): void
    {
        $response = new Zend_Http_Response_Stream(200, []);
        $result = $response->setStreamName('/tmp/test-stream');

        $this->assertEquals('/tmp/test-stream', $response->getStreamName());
        $this->assertSame($response, $result, 'setStreamName should return $this for fluent interface');
    }

    // ---------------------------------------------------------------
    // getCleanup / setCleanup
    // ---------------------------------------------------------------

    public function testGetCleanupReturnsNullByDefault(): void
    {
        $response = new Zend_Http_Response_Stream(200, []);
        $this->assertNull($response->getCleanup());
    }

    public function testSetCleanupDefaultsToTrue(): void
    {
        $response = new Zend_Http_Response_Stream(200, []);
        $response->setCleanup();
        $this->assertTrue($response->getCleanup());
    }

    public function testSetCleanupFalse(): void
    {
        $response = new Zend_Http_Response_Stream(200, []);
        $response->setCleanup(true);
        $response->setCleanup(false);
        $this->assertFalse($response->getCleanup());
    }

    // ---------------------------------------------------------------
    // fromStream
    // ---------------------------------------------------------------

    public function testFromStreamCreatesResponseFromStringAndResource(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'This is the body');
        rewind($stream);

        $responseStr = "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\n";
        $response = Zend_Http_Response_Stream::fromStream($responseStr, $stream);

        $this->assertInstanceOf('Zend_Http_Response_Stream', $response);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('OK', $response->getMessage());
        $this->assertEquals('1.1', $response->getVersion());
        $this->assertEquals('text/plain', $response->getHeader('Content-Type'));
        $this->assertEquals('This is the body', $response->getBody());
    }

    public function testFromStreamWith404(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'Not Found');
        rewind($stream);

        $responseStr = "HTTP/1.0 404 Not Found\r\nContent-Type: text/html\r\n\r\n";
        $response = Zend_Http_Response_Stream::fromStream($responseStr, $stream);

        $this->assertEquals(404, $response->getStatus());
        $this->assertEquals('1.0', $response->getVersion());
        $this->assertEquals('Not Found', $response->getBody());
    }

    // ---------------------------------------------------------------
    // getBody reads from stream
    // ---------------------------------------------------------------

    public function testGetBodyReadsFromStreamAndClosesIt(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'hello world');
        rewind($stream);

        $response = new Zend_Http_Response_Stream(200, []);
        $response->setStream($stream);

        $body = $response->getBody();
        $this->assertEquals('hello world', $body);

        // Stream should be closed after reading
        $this->assertNull($response->getStream());
    }

    public function testGetBodyReadsFullStreamWhenNoContentLength(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'full stream content');
        rewind($stream);

        $responseStr = "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\n";
        $response = Zend_Http_Response_Stream::fromStream($responseStr, $stream);

        $body = $response->getBody();
        $this->assertEquals('full stream content', $body);
    }

    public function testGetBodyWithNoStreamReturnsRegularBody(): void
    {
        $response = new Zend_Http_Response_Stream(200, [], 'regular body');
        $this->assertEquals('regular body', $response->getBody());
    }

    // ---------------------------------------------------------------
    // getRawBody reads from stream
    // ---------------------------------------------------------------

    public function testGetRawBodyReadsFromStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'raw content');
        rewind($stream);

        $response = new Zend_Http_Response_Stream(200, []);
        $response->setStream($stream);

        $body = $response->getRawBody();
        $this->assertEquals('raw content', $body);

        // Stream should be closed
        $this->assertNull($response->getStream());
    }

    public function testGetRawBodyWithNoStreamReturnsBody(): void
    {
        $response = new Zend_Http_Response_Stream(200, [], 'some body');
        $this->assertEquals('some body', $response->getRawBody());
    }

    // ---------------------------------------------------------------
    // Calling getBody twice returns the same content (stream consumed once)
    // ---------------------------------------------------------------

    public function testGetBodyCalledTwiceReturnsSameContent(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'double read');
        rewind($stream);

        $response = new Zend_Http_Response_Stream(200, []);
        $response->setStream($stream);

        $first = $response->getBody();
        $second = $response->getBody();

        $this->assertEquals('double read', $first);
        $this->assertEquals($first, $second);
    }

    // ---------------------------------------------------------------
    // Destructor / cleanup
    // ---------------------------------------------------------------

    public function testDestructorClosesOpenStream(): void
    {
        $stream = fopen('php://memory', 'r+');

        $response = new Zend_Http_Response_Stream(200, []);
        $response->setStream($stream);

        // Trigger destructor
        unset($response);

        // Stream should now be closed
        $this->assertFalse(is_resource($stream));
    }

    public function testDestructorDeletesFileWhenCleanupIsTrue(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'zend_http_test_');
        $this->tempFiles[] = $tempFile;
        file_put_contents($tempFile, 'test data');

        $stream = fopen($tempFile, 'r');

        $response = new Zend_Http_Response_Stream(200, []);
        $response->setStream($stream);
        $response->setStreamName($tempFile);
        $response->setCleanup(true);

        unset($response);

        $this->assertFileDoesNotExist($tempFile);
    }

    public function testDestructorDoesNotDeleteFileWhenCleanupIsFalse(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'zend_http_test_');
        $this->tempFiles[] = $tempFile;
        file_put_contents($tempFile, 'keep me');

        $stream = fopen($tempFile, 'r');

        $response = new Zend_Http_Response_Stream(200, []);
        $response->setStream($stream);
        $response->setStreamName($tempFile);
        $response->setCleanup(false);

        unset($response);

        $this->assertFileExists($tempFile);
    }

    // ---------------------------------------------------------------
    // Response status helpers
    // ---------------------------------------------------------------

    public function testIsSuccessful(): void
    {
        $response = new Zend_Http_Response_Stream(200, []);
        $this->assertTrue($response->isSuccessful());
    }

    public function testIsError(): void
    {
        $response = new Zend_Http_Response_Stream(500, []);
        $this->assertTrue($response->isError());
    }

    public function testIsRedirect(): void
    {
        $response = new Zend_Http_Response_Stream(302, []);
        $this->assertTrue($response->isRedirect());
    }

    // ---------------------------------------------------------------
    // Headers
    // ---------------------------------------------------------------

    public function testGetHeadersFromStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'body');
        rewind($stream);

        $responseStr = "HTTP/1.1 200 OK\r\nX-Custom: foo\r\nContent-Type: text/html\r\n\r\n";
        $response = Zend_Http_Response_Stream::fromStream($responseStr, $stream);

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('X-custom', $headers);
        $this->assertEquals('foo', $headers['X-custom']);
    }
}
