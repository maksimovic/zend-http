<?php

use PHPUnit\Framework\TestCase;

class Zend_Http_UserAgent_DeviceTypeTest extends TestCase
{
    // ---------------------------------------------------------------
    // Desktop device
    // ---------------------------------------------------------------

    public function testDesktopMatchReturnsTrue(): void
    {
        $this->assertTrue(Zend_Http_UserAgent_Desktop::match('anything', []));
    }

    public function testDesktopGetType(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/91.0',
            []
        );
        $this->assertEquals('desktop', $device->getType());
    }

    public function testDesktopHasFeatureIsDesktop(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/91.0',
            []
        );
        $this->assertTrue($device->hasFeature('is_desktop'));
        $this->assertTrue($device->getFeature('is_desktop'));
    }

    public function testDesktopHasFeatureNotBot(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0',
            []
        );
        $this->assertFalse($device->getFeature('is_bot'));
    }

    public function testDesktopGetAllFeatures(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0',
            []
        );
        $features = $device->getAllFeatures();
        $this->assertIsArray($features);
        $this->assertArrayHasKey('is_desktop', $features);
        $this->assertArrayHasKey('php_version', $features);
    }

    public function testDesktopGetAllGroups(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0',
            []
        );
        $groups = $device->getAllGroups();
        $this->assertIsArray($groups);
        $this->assertArrayHasKey('product_info', $groups);
    }

    public function testDesktopGetGroup(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0',
            []
        );
        $group = $device->getGroup('product_info');
        $this->assertIsArray($group);
        $this->assertContains('is_desktop', $group);
    }

    public function testDesktopSerializeAndUnserialize(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0',
            []
        );

        $serialized = $device->serialize();
        $this->assertIsString($serialized);

        $restored = new Zend_Http_UserAgent_Desktop();
        $restored->unserialize($serialized);
        $this->assertEquals($device->getAllFeatures(), $restored->getAllFeatures());
    }

    public function testDesktopSetFeature(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $device->setFeature('custom_feat', 'val', 'custom_group');
        $this->assertEquals('val', $device->getFeature('custom_feat'));
        $this->assertContains('custom_feat', $device->getGroup('custom_group'));
    }

    public function testDesktopHasFeatureReturnsFalseForMissing(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $this->assertFalse($device->hasFeature('nonexistent'));
    }

    public function testDesktopGetFeatureReturnsNullForMissing(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $this->assertNull($device->getFeature('nonexistent'));
    }

    public function testDesktopGetBrowser(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            []
        );
        $browser = $device->getBrowser();
        $this->assertIsString($browser);
    }

    public function testDesktopGetBrowserVersion(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            []
        );
        $version = $device->getBrowserVersion();
        $this->assertIsString($version);
    }

    public function testDesktopGetUserAgent(): void
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0';
        $device = new Zend_Http_UserAgent_Desktop($ua, []);
        $this->assertEquals($ua, $device->getUserAgent());
    }

    public function testDesktopGetImageFormatSupport(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $formats = $device->getImageFormatSupport();
        $this->assertIsArray($formats);
    }

    public function testDesktopGetMaxImageWidth(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $this->assertNull($device->getMaxImageWidth());
    }

    public function testDesktopGetMaxImageHeight(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $this->assertNull($device->getMaxImageHeight());
    }

    public function testDesktopGetPreferredMarkup(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $markup = $device->getPreferredMarkup();
        $this->assertEquals('xhtml', $markup);
    }

    public function testDesktopGetXhtmlSupportLevel(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $level = $device->getXhtmlSupportLevel();
        $this->assertEquals(4, $level);
    }

    public function testDesktopHasFlashSupport(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $this->assertIsBool($device->hasFlashSupport());
    }

    public function testDesktopHasPdfSupport(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $this->assertIsBool($device->hasPdfSupport());
    }

    public function testDesktopHasPhoneNumber(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $this->assertIsBool($device->hasPhoneNumber());
    }

    public function testDesktopHttpsSupport(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $this->assertIsBool($device->httpsSupport());
    }

    // ---------------------------------------------------------------
    // Desktop with server vars (cover more branches in _getDefaultFeatures)
    // ---------------------------------------------------------------

    public function testDesktopWithServerVars(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Mozilla/5.0 (Windows NT 10.0) Chrome/91.0',
            [
                'remote_addr'            => '127.0.0.1',
                'server_software'        => 'Apache/2.4.41 (Unix)',
                'http_accept'            => 'text/html',
                'http_accept_language'   => 'en-US',
                'server_addr'            => '10.0.0.1',
                'server_name'            => 'example.com',
            ]
        );
        $this->assertTrue($device->hasFeature('client_ip'));
        $this->assertEquals('127.0.0.1', $device->getFeature('client_ip'));
        $this->assertTrue($device->hasFeature('server_os'));
        $this->assertTrue($device->hasFeature('server_os_version'));
        $this->assertTrue($device->hasFeature('server_http_accept'));
        $this->assertTrue($device->hasFeature('server_http_accept_language'));
        $this->assertTrue($device->hasFeature('server_ip'));
        $this->assertTrue($device->hasFeature('server_name'));
    }

    public function testDesktopWithIISServer(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Mozilla/5.0 (Windows NT 10.0) Chrome/91.0',
            ['server_software' => 'Microsoft-IIS/10.0 (Win64)']
        );
        $this->assertTrue($device->hasFeature('server_os'));
        $this->assertEquals('iis', $device->getFeature('server_os'));
    }

    public function testDesktopWithForwardedForIP(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Chrome/91',
            ['http_x_forwarded_for' => '192.168.1.100']
        );
        $this->assertEquals('192.168.1.100', $device->getFeature('client_ip'));
    }

    public function testDesktopWithClientIP(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Chrome/91',
            ['http_client_ip' => '10.0.0.50']
        );
        $this->assertEquals('10.0.0.50', $device->getFeature('client_ip'));
    }

    // ---------------------------------------------------------------
    // Bot device
    // ---------------------------------------------------------------

    public function testBotMatchReturnsTrueForGooglebot(): void
    {
        $this->assertTrue(Zend_Http_UserAgent_Bot::match('Googlebot/2.1', []));
    }

    public function testBotMatchReturnsFalseForChrome(): void
    {
        $this->assertFalse(Zend_Http_UserAgent_Bot::match('Chrome/91.0', []));
    }

    public function testBotGetType(): void
    {
        $device = new Zend_Http_UserAgent_Bot('Googlebot/2.1', []);
        $this->assertEquals('bot', $device->getType());
        $this->assertTrue($device->getFeature('is_bot'));
    }

    // ---------------------------------------------------------------
    // Text device
    // ---------------------------------------------------------------

    public function testTextMatchReturnsTrueForLynx(): void
    {
        $this->assertTrue(Zend_Http_UserAgent_Text::match('Lynx/2.8.7', []));
    }

    public function testTextMatchReturnsFalseForChrome(): void
    {
        $this->assertFalse(Zend_Http_UserAgent_Text::match('Chrome/91.0', []));
    }

    public function testTextGetType(): void
    {
        $device = new Zend_Http_UserAgent_Text('Lynx/2.8.7', []);
        $this->assertEquals('text', $device->getType());
        $this->assertTrue($device->getFeature('is_text'));
    }

    public function testTextGetImageFormatSupportReturnsNull(): void
    {
        $device = new Zend_Http_UserAgent_Text('Lynx/2.8.7', []);
        $this->assertNull($device->getImageFormatSupport());
    }

    public function testTextGetPreferredMarkup(): void
    {
        $device = new Zend_Http_UserAgent_Text('Lynx/2.8.7', []);
        $this->assertEquals('xhtml', $device->getPreferredMarkup());
    }

    public function testTextGetXhtmlSupportLevel(): void
    {
        $device = new Zend_Http_UserAgent_Text('Lynx/2.8.7', []);
        $this->assertEquals(1, $device->getXhtmlSupportLevel());
    }

    public function testTextHasFlashSupportReturnsFalse(): void
    {
        $device = new Zend_Http_UserAgent_Text('Lynx/2.8.7', []);
        $this->assertFalse($device->hasFlashSupport());
    }

    public function testTextHasPdfSupportReturnsFalse(): void
    {
        $device = new Zend_Http_UserAgent_Text('Lynx/2.8.7', []);
        $this->assertFalse($device->hasPdfSupport());
    }

    // ---------------------------------------------------------------
    // Feed device
    // ---------------------------------------------------------------

    public function testFeedMatchReturnsTrueForFeedFetcher(): void
    {
        $this->assertTrue(Zend_Http_UserAgent_Feed::match('FeedFetcher-Google; (+http://www.google.com/feedfetcher.html)', []));
    }

    public function testFeedGetType(): void
    {
        $device = new Zend_Http_UserAgent_Feed('FeedFetcher-Google', []);
        $this->assertEquals('feed', $device->getType());
    }

    // ---------------------------------------------------------------
    // Checker device
    // ---------------------------------------------------------------

    public function testCheckerMatchReturnsTrueForCheckLink(): void
    {
        $this->assertTrue(Zend_Http_UserAgent_Checker::match('W3C-checklink/4.2.1', []));
    }

    // ---------------------------------------------------------------
    // Console device
    // ---------------------------------------------------------------

    public function testConsoleMatchForWii(): void
    {
        $this->assertTrue(Zend_Http_UserAgent_Console::match(
            'Opera/9.30 (Nintendo Wii; U; ; 2071; Wii Shop Channel/1.0; en)',
            []
        ));
    }

    // ---------------------------------------------------------------
    // Email device
    // ---------------------------------------------------------------

    public function testEmailMatchForThunderbird(): void
    {
        $this->assertTrue(Zend_Http_UserAgent_Email::match('Mozilla Thunderbird/78.0', []));
    }

    // ---------------------------------------------------------------
    // Offline device
    // ---------------------------------------------------------------

    public function testOfflineMatchForWget(): void
    {
        $this->assertTrue(Zend_Http_UserAgent_Offline::match('Wget/1.12 (linux-gnu)', []));
    }

    // ---------------------------------------------------------------
    // Validator device
    // ---------------------------------------------------------------

    public function testValidatorMatchForCSS(): void
    {
        $this->assertTrue(Zend_Http_UserAgent_Validator::match('Jigsaw/2.2.6 W3C_CSS_Validator_JFouffa/2.0', []));
    }

    // ---------------------------------------------------------------
    // Probe device
    // ---------------------------------------------------------------

    public function testProbeMatchForWitbe(): void
    {
        $this->assertTrue(Zend_Http_UserAgent_Probe::match('witbe.net robot', []));
    }

    // ---------------------------------------------------------------
    // Spam device
    // ---------------------------------------------------------------

    public function testSpamMatchReturnsFalseForNormalUA(): void
    {
        // Spam signatures are empty, so match always returns false via _matchAgentAgainstSignatures
        $this->assertFalse(Zend_Http_UserAgent_Spam::match('Mozilla/5.0 Chrome/91.0', []));
    }

    // ---------------------------------------------------------------
    // Mobile device
    // ---------------------------------------------------------------

    public function testMobileMatchForIPhone(): void
    {
        $this->assertTrue(Zend_Http_UserAgent_Mobile::match(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
            []
        ));
    }

    public function testMobileMatchReturnsFalseForDesktopChrome(): void
    {
        $this->assertFalse(Zend_Http_UserAgent_Mobile::match(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            []
        ));
    }

    public function testMobileGetType(): void
    {
        $device = new Zend_Http_UserAgent_Mobile(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
            []
        );
        $this->assertEquals('mobile', $device->getType());
        $this->assertTrue($device->getFeature('is_mobile'));
    }

    // ---------------------------------------------------------------
    // __serialize / __unserialize
    // ---------------------------------------------------------------

    public function testSerializeUnserialize(): void
    {
        $device = new Zend_Http_UserAgent_Desktop(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0',
            []
        );
        $spec = $device->__serialize();
        $this->assertIsArray($spec);
        $this->assertArrayHasKey('_aFeatures', $spec);
        $this->assertArrayHasKey('_browser', $spec);

        $restored = new Zend_Http_UserAgent_Desktop();
        $restored->__unserialize($spec);
        $this->assertEquals($device->getAllFeatures(), $restored->getAllFeatures());
    }

    // ---------------------------------------------------------------
    // setGroup with duplicate feature
    // ---------------------------------------------------------------

    public function testSetGroupDoesNotDuplicateFeature(): void
    {
        $device = new Zend_Http_UserAgent_Desktop('Chrome', []);
        $device->setGroup('test_group', 'feat1');
        $device->setGroup('test_group', 'feat1');
        $group = $device->getGroup('test_group');
        $this->assertCount(1, array_filter($group, fn($v) => $v === 'feat1'));
    }
}
