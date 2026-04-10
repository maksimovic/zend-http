<?php

use PHPUnit\Framework\TestCase;

/**
 * UserAgent tests are skipped because they require Zend_Config, Zend_Loader_PluginLoader,
 * and other dependencies not available in this standalone package.
 */
class Zend_Http_UserAgentTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped(
            'UserAgent tests require Zend_Config, Zend_Loader_PluginLoader and other unavailable dependencies'
        );
    }

    public function testPlaceholder(): void
    {
        // placeholder
    }
}
