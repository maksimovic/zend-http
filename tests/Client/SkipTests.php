<?php

use PHPUnit\Framework\TestCase;

class Zend_Http_Client_Skip_SocketTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped("Zend_Http_Client dynamic tests are not enabled");
    }

    public function testSocket(): void
    {
        // placeholder
    }
}

class Zend_Http_Client_Skip_ProxyAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped("Zend_Http_Client proxy server tests are not enabled");
    }

    public function testProxyAdapter(): void
    {
        // placeholder
    }
}
