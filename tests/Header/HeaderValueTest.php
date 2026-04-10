<?php

use PHPUnit\Framework\TestCase;

class Zend_Http_Header_HeaderValueTest extends TestCase
{
    public static function getFilterValues(): array
    {
        return array(
            array("This is a\n test", "This is a test"),
            array("This is a\r test", "This is a test"),
            array("This is a\n\r test", "This is a test"),
            array("This is a\r\n  test", "This is a  test"),
            array("This is a \r\ntest", "This is a test"),
            array("This is a \r\n\n test", "This is a  test"),
            array("This is a\n\n test", "This is a test"),
            array("This is a\r\r test", "This is a test"),
            array("This is a \r\r\n test", "This is a  test"),
            array("This is a \r\n\r\ntest", "This is a test"),
            array("This is a \r\n\n\r\n test", "This is a  test")
        );
    }

    /**
     * @dataProvider getFilterValues
     */
    public function testFiltersValuesPerRfc7230($value, $expected): void
    {
        $this->assertEquals($expected, Zend_Http_Header_HeaderValue::filter($value));
    }

    public static function validateValues(): array
    {
        return array(
            array("This is a\n test", 'assertFalse'),
            array("This is a\r test", 'assertFalse'),
            array("This is a\n\r test", 'assertFalse'),
            array("This is a\r\n  test", 'assertFalse'),
            array("This is a \r\ntest", 'assertFalse'),
            array("This is a \r\n\n test", 'assertFalse'),
            array("This is a\n\n test", 'assertFalse'),
            array("This is a\r\r test", 'assertFalse'),
            array("This is a \r\r\n test", 'assertFalse'),
            array("This is a \r\n\r\ntest", 'assertFalse'),
            array("This is a \r\n\n\r\n test", 'assertFalse')
        );
    }

    /**
     * @dataProvider validateValues
     */
    public function testValidatesValuesPerRfc7230($value, $assertion): void
    {
        $this->{$assertion}(Zend_Http_Header_HeaderValue::isValid($value));
    }

    public static function assertValues(): array
    {
        return array(
            array("This is a\n test"),
            array("This is a\r test"),
            array("This is a\n\r test"),
            array("This is a \r\ntest"),
            array("This is a \r\n\n test"),
            array("This is a\n\n test"),
            array("This is a\r\r test"),
            array("This is a \r\r\n test"),
            array("This is a \r\n\r\ntest"),
            array("This is a \r\n\n\r\n test")
        );
    }

    /**
     * @dataProvider assertValues
     */
    public function testAssertValidRaisesExceptionForInvalidValue($value): void
    {
        $this->expectException('Zend_Http_Header_Exception_InvalidArgumentException');
        Zend_Http_Header_HeaderValue::assertValid($value);
    }
}
