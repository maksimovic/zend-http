<?php

use PHPUnit\Framework\TestCase;

class Zend_Http_Header_SetCookieTest extends TestCase
{
    public function testSetCookieConstructor(): void
    {
        $setCookieHeader = new Zend_Http_Header_SetCookie(
            'myname', 'myvalue', 'Wed, 13-Jan-2021 22:23:01 GMT',
            '/accounts', 'docs.foo.com', true, true, 99, 9
        );
        $this->assertEquals('myname', $setCookieHeader->getName());
        $this->assertEquals('myvalue', $setCookieHeader->getValue());
        $this->assertEquals('Wed, 13-Jan-2021 22:23:01 GMT', $setCookieHeader->getExpires());
        $this->assertEquals('/accounts', $setCookieHeader->getPath());
        $this->assertEquals('docs.foo.com', $setCookieHeader->getDomain());
        $this->assertTrue($setCookieHeader->isSecure());
        $this->assertTrue($setCookieHeader->isHttpOnly());
        $this->assertEquals(99, $setCookieHeader->getMaxAge());
        $this->assertEquals(9, $setCookieHeader->getVersion());
    }

    public function testSetCookieFromStringCreatesValidSetCookieHeader(): void
    {
        $setCookieHeader = Zend_Http_Header_SetCookie::fromString('Set-Cookie: xxx');
        $this->assertTrue($setCookieHeader instanceof Zend_Http_Header_SetCookie);
    }

    public function testSetCookieFromStringCanCreateSingleHeader(): void
    {
        $setCookieHeader = Zend_Http_Header_SetCookie::fromString('Set-Cookie: myname=myvalue');
        $this->assertTrue($setCookieHeader instanceof Zend_Http_Header_SetCookie);
        $this->assertEquals('myname', $setCookieHeader->getName());
        $this->assertEquals('myvalue', $setCookieHeader->getValue());

        $setCookieHeader = Zend_Http_Header_SetCookie::fromString(
            'set-cookie: myname=myvalue; Domain=docs.foo.com; Path=/accounts;'
            . 'Expires=Wed, 13-Jan-2021 22:23:01 GMT; Secure; HttpOnly'
        );
        $this->assertTrue($setCookieHeader instanceof Zend_Http_Header_SetCookie);
        $this->assertEquals('myname', $setCookieHeader->getName());
        $this->assertEquals('myvalue', $setCookieHeader->getValue());
        $this->assertEquals('docs.foo.com', $setCookieHeader->getDomain());
        $this->assertEquals('/accounts', $setCookieHeader->getPath());
        $this->assertEquals('Wed, 13-Jan-2021 22:23:01 GMT', $setCookieHeader->getExpires());
        $this->assertTrue($setCookieHeader->isSecure());
        $this->assertTrue($setCookieHeader->isHttponly());
    }

    public function testSetCookieFromStringCanCreateMultipleHeaders(): void
    {
        $setCookieHeaders = Zend_Http_Header_SetCookie::fromString(
            'Set-Cookie: myname=myvalue, '
            . 'someothername=someothervalue; Domain=docs.foo.com; Path=/accounts;'
            . 'Expires=Wed, 13-Jan-2021 22:23:01 GMT; Secure; HttpOnly'
        );
        $this->assertIsArray($setCookieHeaders);

        $setCookieHeader = $setCookieHeaders[0];
        $this->assertTrue($setCookieHeader instanceof Zend_Http_Header_SetCookie);
        $this->assertEquals('myname', $setCookieHeader->getName());
        $this->assertEquals('myvalue', $setCookieHeader->getValue());

        $setCookieHeader = $setCookieHeaders[1];
        $this->assertTrue($setCookieHeader instanceof Zend_Http_Header_SetCookie);
        $this->assertEquals('someothername', $setCookieHeader->getName());
        $this->assertEquals('someothervalue', $setCookieHeader->getValue());
        $this->assertEquals('Wed, 13-Jan-2021 22:23:01 GMT', $setCookieHeader->getExpires());
        $this->assertEquals('docs.foo.com', $setCookieHeader->getDomain());
        $this->assertEquals('/accounts', $setCookieHeader->getPath());
        $this->assertTrue($setCookieHeader->isSecure());
        $this->assertTrue($setCookieHeader->isHttponly());
    }

    public function testSetCookieGetFieldNameReturnsHeaderName(): void
    {
        $setCookieHeader = new Zend_Http_Header_SetCookie();
        $this->assertEquals('Set-Cookie', $setCookieHeader->getFieldName());
    }

    public function testSetCookieGetFieldValueReturnsProperValue(): void
    {
        $setCookieHeader = new Zend_Http_Header_SetCookie();
        $setCookieHeader->setName('myname');
        $setCookieHeader->setValue('myvalue');
        $setCookieHeader->setExpires('Wed, 13-Jan-2021 22:23:01 GMT');
        $setCookieHeader->setDomain('docs.foo.com');
        $setCookieHeader->setPath('/accounts');
        $setCookieHeader->setSecure(true);
        $setCookieHeader->setHttponly(true);

        $target = 'myname=myvalue; Expires=Wed, 13-Jan-2021 22:23:01 GMT;'
            . ' Domain=docs.foo.com; Path=/accounts;'
            . ' Secure; HttpOnly';

        $this->assertEquals($target, $setCookieHeader->getFieldValue());
    }

    public function testSetCookieToStringReturnsHeaderFormattedString(): void
    {
        $setCookieHeader = new Zend_Http_Header_SetCookie();
        $setCookieHeader->setName('myname');
        $setCookieHeader->setValue('myvalue');
        $setCookieHeader->setExpires('Wed, 13-Jan-2021 22:23:01 GMT');
        $setCookieHeader->setDomain('docs.foo.com');
        $setCookieHeader->setPath('/accounts');
        $setCookieHeader->setSecure(true);
        $setCookieHeader->setHttponly(true);

        $target = 'Set-Cookie: myname=myvalue; Expires=Wed, 13-Jan-2021 22:23:01 GMT;'
            . ' Domain=docs.foo.com; Path=/accounts;'
            . ' Secure; HttpOnly';

        $this->assertEquals($target, $setCookieHeader->toString());
    }

    public function testSetCookieCanAppendOtherHeadersInWhenCreatingString(): void
    {
        $setCookieHeader = new Zend_Http_Header_SetCookie();
        $setCookieHeader->setName('myname');
        $setCookieHeader->setValue('myvalue');
        $setCookieHeader->setExpires('Wed, 13-Jan-2021 22:23:01 GMT');
        $setCookieHeader->setDomain('docs.foo.com');
        $setCookieHeader->setPath('/accounts');
        $setCookieHeader->setSecure(true);
        $setCookieHeader->setHttponly(true);

        $appendCookie = new Zend_Http_Header_SetCookie('othername', 'othervalue');
        $headerLine = $setCookieHeader->toStringMultipleHeaders(array($appendCookie));

        $target = 'Set-Cookie: myname=myvalue; Expires=Wed, 13-Jan-2021 22:23:01 GMT;'
            . ' Domain=docs.foo.com; Path=/accounts;'
            . ' Secure; HttpOnly, othername=othervalue';
        $this->assertEquals($target, $headerLine);
    }

    public function testZF2_169(): void
    {
        $cookie = 'Set-Cookie: leo_auth_token="example"; Version=1; Max-Age=1799; Expires=Mon, 20-Feb-2012 02:49:57 GMT; Path=/';
        $setCookieHeader = Zend_Http_Header_SetCookie::fromString($cookie);
        $this->assertEquals($cookie, $setCookieHeader->toString());
    }

    public function testGetFieldName(): void
    {
        $c = new Zend_Http_Header_SetCookie();
        $this->assertEquals('Set-Cookie', $c->getFieldName());
    }

    /**
     * @dataProvider validCookieWithInfoProvider
     */
    public function testGetFieldValue($cStr, $info, $expected): void
    {
        $cookie = Zend_Http_Header_SetCookie::fromString($cStr);
        if (! $cookie instanceof Zend_Http_Header_SetCookie) {
            $this->fail("Failed creating a cookie object from '$cStr'");
        }
        $this->assertEquals($expected, $cookie->getFieldValue());
        $this->assertEquals($cookie->getFieldName() . ': ' . $expected, (string)$cookie);
    }

    /**
     * @dataProvider validCookieWithInfoProvider
     */
    public function testToString($cStr, $info, $expected): void
    {
        $cookie = Zend_Http_Header_SetCookie::fromString($cStr);
        if (! $cookie instanceof Zend_Http_Header_SetCookie) {
            $this->fail("Failed creating a cookie object from '$cStr'");
        }
        $this->assertEquals($cookie->getFieldName() . ': ' . $expected, $cookie->toString());
    }

    /**
     * Tests that depend on Zend_Controller_Response_HttpTestCase are skipped.
     *
     * @dataProvider validCookieWithInfoProvider
     */
    public function testAddingAsRawHeaderToResponseObject($cStr, $info, $expected): void
    {
        $this->markTestSkipped('Requires Zend_Controller_Response_HttpTestCase which is not available');
    }

    public function testMultipleCookies(): void
    {
        $this->markTestSkipped('Requires Zend_Controller_Response_HttpTestCase which is not available');
    }

    public static function validCookieWithInfoProvider(): array
    {
        $now = time();
        $yesterday = $now - (3600 * 24);

        return array(
            array(
                'Set-Cookie: justacookie=foo; domain=example.com',
                array(
                    'name'    => 'justacookie',
                    'value'   => 'foo',
                    'domain'  => 'example.com',
                    'path'    => '/',
                    'expires' => null,
                    'secure'  => false,
                    'httponly'=> false
                ),
                'justacookie=foo; Domain=example.com'
            ),
            array(
                'Set-Cookie: expires=tomorrow; secure; path=/Space Out/; expires=Tue, 21-Nov-2006 08:33:44 GMT; domain=.example.com',
                array(
                    'name'    => 'expires',
                    'value'   => 'tomorrow',
                    'domain'  => '.example.com',
                    'path'    => '/Space Out/',
                    'expires' => strtotime('Tue, 21-Nov-2006 08:33:44 GMT'),
                    'secure'  => true,
                    'httponly'=> false
                ),
                'expires=tomorrow; Expires=Tue, 21-Nov-2006 08:33:44 GMT; Domain=.example.com; Path=/Space Out/; Secure'
            ),
            array(
                'Set-Cookie: domain=unittests; expires=' . gmdate('D, d-M-Y H:i:s', $now) . ' GMT; domain=example.com; path=/some%20value/',
                array(
                    'name'    => 'domain',
                    'value'   => 'unittests',
                    'domain'  => 'example.com',
                    'path'    => '/some%20value/',
                    'expires' => $now,
                    'secure'  => false,
                    'httponly'=> false
                ),
                'domain=unittests; Expires=' . gmdate('D, d-M-Y H:i:s', $now) . ' GMT; Domain=example.com; Path=/some%20value/'
            ),
            array(
                'Set-Cookie: path=indexAction; path=/; domain=.foo.com; expires=' . gmdate('D, d-M-Y H:i:s', $yesterday) . ' GMT',
                array(
                    'name'    => 'path',
                    'value'   => 'indexAction',
                    'domain'  => '.foo.com',
                    'path'    => '/',
                    'expires' => $yesterday,
                    'secure'  => false,
                    'httponly'=> false
                ),
                'path=indexAction; Expires=' . gmdate('D, d-M-Y H:i:s', $yesterday) . ' GMT; Domain=.foo.com; Path=/'
            ),
            array(
                'Set-Cookie: secure=sha1; secure; SECURE; domain=some.really.deep.domain.com',
                array(
                    'name'    => 'secure',
                    'value'   => 'sha1',
                    'domain'  => 'some.really.deep.domain.com',
                    'path'    => '/',
                    'expires' => null,
                    'secure'  => true,
                    'httponly'=> false
                ),
                'secure=sha1; Domain=some.really.deep.domain.com; Secure'
            ),
            array(
                'Set-Cookie: justacookie=foo; domain=example.com; httpOnly',
                array(
                    'name'    => 'justacookie',
                    'value'   => 'foo',
                    'domain'  => 'example.com',
                    'path'    => '/',
                    'expires' => null,
                    'secure'  => false,
                    'httponly'=> true
                ),
                'justacookie=foo; Domain=example.com; HttpOnly'
            ),
            array(
                'Set-Cookie: PHPSESSID=123456789+abcd%2Cef; secure; domain=.localdomain; path=/foo/baz; expires=Tue, 21-Nov-2006 08:33:44 GMT;',
                array(
                    'name'    => 'PHPSESSID',
                    'value'   => '123456789+abcd%2Cef',
                    'domain'  => '.localdomain',
                    'path'    => '/foo/baz',
                    'expires' => 'Tue, 21-Nov-2006 08:33:44 GMT',
                    'secure'  => true,
                    'httponly'=> false
                ),
                'PHPSESSID=123456789%2Babcd%252Cef; Expires=Tue, 21-Nov-2006 08:33:44 GMT; Domain=.localdomain; Path=/foo/baz; Secure'
            ),
            array(
                'Set-Cookie: myname=myvalue; Domain=docs.foo.com; Path=/accounts; Expires=Wed, 13-Jan-2021 22:23:01 GMT; Secure; HttpOnly',
                array(
                    'name'    => 'myname',
                    'value'   => 'myvalue',
                    'domain'  => 'docs.foo.com',
                    'path'    => '/accounts',
                    'expires' => 'Wed, 13-Jan-2021 22:23:01 GMT',
                    'secure'  => true,
                    'httponly'=> true
                ),
                'myname=myvalue; Expires=Wed, 13-Jan-2021 22:23:01 GMT; Domain=docs.foo.com; Path=/accounts; Secure; HttpOnly'
            ),
        );
    }

    public static function invalidCookieComponentValues(): array
    {
        return array(
            'setName'   => array('setName', "This\r\nis\nan\revil\r\n\r\nvalue"),
            'setValue'  => array('setValue', "This\r\nis\nan\revil\r\n\r\nvalue"),
            'setDomain' => array('setDomain', "This\r\nis\nan\revil\r\n\r\nvalue"),
            'setPath'   => array('setPath', "This\r\nis\nan\revil\r\n\r\nvalue"),
        );
    }

    /**
     * @dataProvider invalidCookieComponentValues
     */
    public function testDoesNotAllowCRLFAttackVectorsViaSetters($setter, $value): void
    {
        $this->expectException('Zend_Http_Header_Exception_InvalidArgumentException');
        $cookie = new Zend_Http_Header_SetCookie();
        $cookie->{$setter}($value);
    }
}
