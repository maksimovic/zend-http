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
        $this->client = new Zend_Http_Client();
    }

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
}
