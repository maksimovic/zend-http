<?php

use PHPUnit\Framework\TestCase;

class Zend_Http_CookieJarTest extends TestCase
{
    public function loadResponse($filename): string
    {
        $message = file_get_contents($filename);
        return preg_replace("#(?<!\r)\n#", "\r\n", $message);
    }

    public function testAddCookie(): void
    {
        $jar = new Zend_Http_CookieJar();
        $this->assertEquals(0, count($jar->getAllCookies()), 'Cookie jar is expected to contain 0 cookies');

        $jar->addCookie('foo=bar; domain=example.com');
        $cookie = $jar->getCookie('http://example.com/', 'foo');
        $this->assertTrue($cookie instanceof Zend_Http_Cookie, '$cookie is expected to be a Cookie object');
        $this->assertEquals('bar', $cookie->getValue(), 'Cookie value is expected to be "bar"');

        $jar->addCookie('cookie=brownie; domain=geekz.co.uk;');
        $this->assertEquals(2, count($jar->getAllCookies()), 'Cookie jar is expected to contain 2 cookies');
    }

    public function testExceptAddInvalidCookie(): void
    {
        $jar = new Zend_Http_CookieJar();
        $exceptionCount = 0;

        try {
            $jar->addCookie('garbage');
            $this->fail('Expected exception was not thrown');
        } catch (Zend_Http_Exception $e) {
            $exceptionCount++;
        }

        try {
            $jar->addCookie(new Zend_Http_Cookiejar());
            $this->fail('Expected exception was not thrown');
        } catch (Zend_Http_Exception $e) {
            $exceptionCount++;
        }

        $this->assertEquals(2, $exceptionCount, 'Expected 2 exceptions for invalid cookies');
    }

    public function testAddCookiesFromResponse(): void
    {
        $jar = new Zend_Http_Cookiejar();
        $res_str = $this->loadResponse(
            dirname(realpath(__FILE__)) . '/_files/response_with_cookies'
        );
        $response = Zend_Http_Response::fromString($res_str);

        $jar->addCookiesFromResponse($response, 'http://www.example.com');

        $this->assertEquals(3, count($jar->getAllCookies()));

        $cookie_str = 'foo=bar;BOFH=Feature+was+not+beta+tested;time=1164234700;';
        $this->assertEquals($cookie_str, $jar->getAllCookies(Zend_Http_CookieJar::COOKIE_STRING_CONCAT));
    }

    /**
     * @dataProvider invalidResponseProvider
     */
    public function testExceptAddCookiesInvalidResponse($resp): void
    {
        $this->expectException('Zend_Http_Exception');
        $jar = new Zend_Http_Cookiejar();
        $jar->addCookiesFromResponse($resp, 'http://www.example.com');
    }

    public static function invalidResponseProvider(): array
    {
        return array(
            array(new stdClass),
            array(null),
            array(12),
            array('hi')
        );
    }

    public function testGetAllCookies(): void
    {
        $jar = new Zend_Http_CookieJar();

        $cookies = array(
            'name=Arthur; domain=camelot.gov.uk',
            'quest=holy+grail; domain=forest.euwing.com',
            'swallow=african; domain=bridge-of-death.net'
        );

        foreach ($cookies as $cookie) {
            $jar->addCookie($cookie);
        }

        $cobjects = $jar->getAllCookies();

        foreach ($cobjects as $id => $cookie) {
            $this->assertStringContainsString((string) $cookie, $cookies[$id]);
        }
    }

    public function testGetAllCookiesAsConcat(): void
    {
        $jar = new Zend_Http_CookieJar();

        $cookies = array(
            'name=Arthur; domain=camelot.gov.uk',
            'quest=holy+grail; domain=forest.euwing.com',
            'swallow=african; domain=bridge-of-death.net'
        );

        foreach ($cookies as $cookie) {
            $jar->addCookie($cookie);
        }

        $expected = 'name=Arthur;quest=holy+grail;swallow=african;';
        $real = $jar->getAllCookies(Zend_Http_CookieJar::COOKIE_STRING_CONCAT);

        $this->assertEquals($expected, $real, 'Concatenated string is not as expected');
    }

    public function testGetAllCookiesAsConcatStrictMode(): void
    {
        $jar = new Zend_Http_CookieJar();

        $cookies = array(
            'name=Arthur; domain=camelot.gov.uk',
            'quest=holy+grail; domain=forest.euwing.com',
            'swallow=african; domain=bridge-of-death.net'
        );

        foreach ($cookies as $cookie) {
            $jar->addCookie($cookie);
        }

        $expected = 'name=Arthur; quest=holy+grail; swallow=african';
        $real = $jar->getAllCookies(Zend_Http_CookieJar::COOKIE_STRING_CONCAT_STRICT);

        $this->assertEquals($expected, $real, 'Concatenated string is not as expected');
    }

    public function testGetCookieAsObject(): void
    {
        $cookie = Zend_Http_Cookie::fromString('foo=bar; domain=www.example.com; path=/tests');
        $jar = new Zend_Http_CookieJar();
        $jar->addCookie($cookie->__toString(), 'http://www.example.com/tests/');

        $cobj = $jar->getCookie('http://www.example.com/tests/', 'foo');

        $this->assertTrue($cobj instanceof Zend_Http_Cookie, '$cobj is not a Cookie object');
        $this->assertEquals($cookie->getName(), $cobj->getName(), 'Cookie name is not as expected');
        $this->assertEquals($cookie->getValue(), $cobj->getValue(), 'Cookie value is not as expected');
        $this->assertEquals($cookie->getDomain(), $cobj->getDomain(), 'Cookie domain is not as expected');
        $this->assertEquals($cookie->getPath(), $cobj->getPath(), 'Cookie path is not as expected');
    }

    public function testGetCookieAsString(): void
    {
        $cookie = Zend_Http_Cookie::fromString('foo=bar; domain=www.example.com; path=/tests');
        $jar = new Zend_Http_CookieJar();
        $jar->addCookie($cookie);

        $cstr = $jar->getCookie('http://www.example.com/tests/', 'foo', Zend_Http_CookieJar::COOKIE_STRING_ARRAY);
        $this->assertEquals($cookie->__toString(), $cstr, 'Cookie string is not the expected string');

        $cstr = $jar->getCookie('http://www.example.com/tests/', 'foo', Zend_Http_CookieJar::COOKIE_STRING_CONCAT);
        $this->assertEquals($cookie->__toString(), $cstr, 'Cookie string is not the expected string');
    }

    public function testGetCookieReturnFalse(): void
    {
        $cookie = Zend_Http_Cookie::fromString('foo=bar; domain=www.example.com; path=/tests');
        $jar = new Zend_Http_CookieJar();
        $jar->addCookie($cookie);

        $cstr = $jar->getCookie('http://www.example.com/tests/', 'otherfoo', Zend_Http_CookieJar::COOKIE_STRING_ARRAY);
        $this->assertFalse($cstr, 'getCookie was expected to return false, no such cookie');

        $cstr = $jar->getCookie('http://www.otherexample.com/tests/', 'foo', Zend_Http_CookieJar::COOKIE_STRING_CONCAT);
        $this->assertFalse($cstr, 'getCookie was expected to return false, no such domain');

        $cstr = $jar->getCookie('http://www.example.com/othertests/', 'foo', Zend_Http_CookieJar::COOKIE_STRING_CONCAT);
        $this->assertFalse($cstr, 'getCookie was expected to return false, no such path');
    }

    public function testExceptGetCookieInvalidUri(): void
    {
        $cookie = Zend_Http_Cookie::fromString('foo=bar; domain=www.example.com; path=/tests');
        $jar = new Zend_Http_CookieJar();
        $jar->addCookie($cookie);
        $exceptionCount = 0;

        try {
            $jar->getCookie('foo.com', 'foo');
            $this->fail('Expected getCookie to throw exception, invalid URI string passed');
        } catch (Zend_Exception $e) {
            $exceptionCount++;
        }

        try {
            $jar->getCookie(Zend_Uri::factory('mailto:nobody@dev.null.com'), 'foo');
            $this->fail('Expected getCookie to throw exception, invalid URI object passed');
        } catch (Zend_Exception $e) {
            $exceptionCount++;
        }

        $this->assertEquals(2, $exceptionCount, 'Expected 2 exceptions for invalid URIs');
    }

    public function testExceptGetCookieInvalidReturnType(): void
    {
        $cookie = Zend_Http_Cookie::fromString('foo=bar; domain=example.com;');
        $jar = new Zend_Http_CookieJar();
        $jar->addCookie($cookie);

        try {
            $jar->getCookie('http://example.com/', 'foo', 5);
            $this->fail('Expected getCookie to throw exception, invalid return type');
        } catch (Zend_Http_Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    /**
     * @dataProvider cookieMatchTestProvider
     */
    public function testGetMatchingCookies($url, $expected): void
    {
        $jar = new Zend_Http_CookieJar();
        $cookies = array(
            Zend_Http_Cookie::fromString('foo1=bar1; domain=.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo2=bar2; domain=foo.com; path=/; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo3=bar3; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() - 3600)),
            Zend_Http_Cookie::fromString('foo4=bar4; domain=.foo.com; path=/;'),
            Zend_Http_Cookie::fromString('foo5=bar5; domain=.foo.com; path=/; secure; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo6=bar6; domain=.foo.com; path=/otherpath; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo7=bar7; domain=www.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo7=bar7; domain=newwww.foo.com; path=/;'),
            Zend_Http_Cookie::fromString('foo8=bar8; domain=subdomain.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
        );

        foreach ($cookies as $cookie) $jar->addCookie($cookie);
        $cookies = $jar->getMatchingCookies($url);
        $this->assertEquals($expected, count($cookies), $jar->getMatchingCookies($url, true, Zend_Http_CookieJar::COOKIE_STRING_CONCAT));
    }

    public static function cookieMatchTestProvider(): array
    {
        return array(
            array('http://www.foo.com/path/file.txt', 4),
            array('http://foo.com/path/file.txt', 3),
            array('https://www.foo.com/path/file.txt', 5),
            array('http://subdomain.foo.com/path', 4),
            array('http://subdomain.foo.com/otherpath', 3),
            array('http://blog.foo.com/news', 2)
        );
    }

    public function testGetMatchingCookiesNoSession(): void
    {
        $jar = new Zend_Http_CookieJar();
        $cookies = array(
            Zend_Http_Cookie::fromString('foo1=bar1; domain=.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo2=bar2; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo3=bar3; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() - 3600)),
            Zend_Http_Cookie::fromString('foo4=bar4; domain=.foo.com; path=/;'),
            Zend_Http_Cookie::fromString('foo5=bar5; domain=.foo.com; path=/; secure; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo6=bar6; domain=.foo.com; path=/otherpath; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo7=bar7; domain=www.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo8=bar8; domain=subdomain.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
        );

        foreach ($cookies as $cookie) $jar->addCookie($cookie);

        $this->assertEquals(8, count($jar->getAllCookies()), 'Cookie count is expected to be 8');

        $cookies = $jar->getMatchingCookies('http://www.foo.com/path/file.txt', false);
        $this->assertEquals(3, count($cookies), 'Cookie count is expected to be 3');

        $cookies = $jar->getMatchingCookies('https://www.foo.com/path/file.txt', false);
        $this->assertEquals(4, count($cookies), 'Cookie count is expected to be 4');
    }

    public function testGetMatchingCookiesWithTime(): void
    {
        $jar = new Zend_Http_CookieJar();
        $cookies = array(
            Zend_Http_Cookie::fromString('foo1=bar1; domain=.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo2=bar2; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() + 7200)),
            Zend_Http_Cookie::fromString('foo3=bar3; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() - 3600)),
            Zend_Http_Cookie::fromString('foo4=bar4; domain=.foo.com; path=/;'),
            Zend_Http_Cookie::fromString('foo5=bar5; domain=.foo.com; path=/; secure; expires=' . date(DATE_COOKIE, time() - 7200)),
            Zend_Http_Cookie::fromString('foo6=bar6; domain=.foo.com; path=/otherpath; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo7=bar7; domain=www.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo8=bar8; domain=subdomain.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
        );

        foreach ($cookies as $cookie) $jar->addCookie($cookie);

        $this->assertEquals(8, count($jar->getAllCookies()), 'Cookie count is expected to be 8');

        $cookies = $jar->getMatchingCookies('http://www.foo.com/path/file.txt', true, Zend_Http_CookieJar::COOKIE_OBJECT, time() + 3700);
        $this->assertEquals(2, count($cookies), 'Cookie count is expected to be 2');

        $cookies = $jar->getMatchingCookies('http://www.foo.com/path/file.txt', true, Zend_Http_CookieJar::COOKIE_OBJECT, time() - 3700);
        $this->assertEquals(5, count($cookies), 'Cookie count is expected to be 5');
    }

    public function testGetMatchingCookiesAsStrings(): void
    {
        $jar = new Zend_Http_CookieJar();
        $cookies = array(
            Zend_Http_Cookie::fromString('foo1=bar1; domain=.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo2=bar2; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo3=bar3; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() - 3600)),
            Zend_Http_Cookie::fromString('foo4=bar4; domain=.foo.com; path=/;'),
            Zend_Http_Cookie::fromString('foo5=bar5; domain=.foo.com; path=/; secure; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo6=bar6; domain=.foo.com; path=/otherpath; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo7=bar7; domain=www.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo8=bar8; domain=subdomain.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
        );

        foreach ($cookies as $cookie) $jar->addCookie($cookie);

        $this->assertEquals(8, count($jar->getAllCookies()), 'Cookie count is expected to be 8');

        $cookies = $jar->getMatchingCookies('http://www.foo.com/path/file.txt', true, Zend_Http_CookieJar::COOKIE_STRING_ARRAY);
        $this->assertIsArray($cookies, '$cookies is expected to be an array, but it is not');
        $this->assertIsString($cookies[0], '$cookies[0] is expected to be a string');;

        $cookies = $jar->getMatchingCookies('http://www.foo.com/path/file.txt', true, Zend_Http_CookieJar::COOKIE_STRING_CONCAT);
        $this->assertIsString($cookies, '$cookies is expected to be a string');
        $expected = 'foo1=bar1;foo2=bar2;foo4=bar4;foo7=bar7;';
        $this->assertEquals($expected, $cookies, 'Concatenated string is not as expected');

        $cookies = $jar->getMatchingCookies('http://www.foo.com/path/file.txt', true, Zend_Http_CookieJar::COOKIE_STRING_CONCAT_STRICT);
        $this->assertIsString($cookies, '$cookies is expected to be a string');
        $expected = 'foo1=bar1; foo2=bar2; foo4=bar4; foo7=bar7';
        $this->assertEquals($expected, $cookies, 'Concatenated string is not as expected');
    }

    public function testExceptGetMatchingCookiesInvalidUri(): void
    {
        $jar = new Zend_Http_CookieJar();
        $exceptionCount = 0;

        try {
            $cookies = $jar->getMatchingCookies('invalid.com', true, Zend_Http_CookieJar::COOKIE_STRING_ARRAY);
            $this->fail('Expected getMatchingCookies to throw exception, invalid URI string passed');
        } catch (Zend_Exception $e) {
            $exceptionCount++;
        }

        try {
            $cookies = $jar->getMatchingCookies(new stdClass(), true, Zend_Http_CookieJar::COOKIE_STRING_ARRAY);
            $this->fail('Expected getCookie to throw exception, invalid URI object passed');
        } catch (Zend_Exception $e) {
            $exceptionCount++;
        }

        $this->assertEquals(2, $exceptionCount, 'Expected 2 exceptions for invalid URIs');
    }

    public function testFromResponse(): void
    {
        $res_str = $this->loadResponse(
            dirname(realpath(__FILE__)) . '/_files/response_with_single_cookie'
        );
        $response = Zend_Http_Response::fromString($res_str);

        $jar = Zend_Http_CookieJar::fromResponse($response, 'http://www.example.com');

        $this->assertTrue($jar instanceof Zend_Http_CookieJar, '$jar is not an instance of CookieJar as expected');
        $this->assertEquals(1, count($jar->getAllCookies()), 'CookieJar expected to contain 1 cookie');
    }

    public function testFromResponseMultiHeader(): void
    {
        $res_str = $this->loadResponse(
            dirname(realpath(__FILE__)) . '/_files/response_with_cookies'
        );
        $response = Zend_Http_Response::fromString($res_str);

        $jar = Zend_Http_CookieJar::fromResponse($response, 'http://www.example.com');

        $this->assertTrue($jar instanceof Zend_Http_CookieJar, '$jar is not an instance of CookieJar as expected');
        $this->assertEquals(3, count($jar->getAllCookies()), 'CookieJar expected to contain 3 cookies');
    }

    public function testMatchPathWithTrailingSlash(): void
    {
        $jar = new Zend_Http_CookieJar();
        $cookies = array(
            Zend_Http_Cookie::fromString('foo1=bar1; domain=.example.com; path=/a/b'),
            Zend_Http_Cookie::fromString('foo2=bar2; domain=.example.com; path=/a/b/')
        );

        foreach ($cookies as $cookie) $jar->addCookie($cookie);
        $cookies = $jar->getMatchingCookies('http://www.example.com/a/b/file.txt');

        $this->assertIsArray($cookies);
        $this->assertEquals(2, count($cookies));
    }

    public function testIteratorAndCountable(): void
    {
        $jar = new Zend_Http_CookieJar();
        $cookies = array(
            Zend_Http_Cookie::fromString('foo1=bar1; domain=.example.com; path=/a/b'),
            Zend_Http_Cookie::fromString('foo2=bar2; domain=.example.com; path=/a/b/')
        );
        foreach ($cookies as $cookie) $jar->addCookie($cookie);
        foreach ($jar as $cookie) {
            $this->assertTrue($cookie instanceof Zend_Http_Cookie);
        }
        $this->assertEquals(2, count($jar));
        $this->assertFalse($jar->isEmpty());
        $jar->reset();
        $this->assertTrue($jar->isEmpty());
    }
}
