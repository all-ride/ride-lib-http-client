<?php

namespace ride\library\http\client;

use \PHPUnit_Framework_TestCase;

class RequestTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->request = new Request('/');
    }

    public function testPort() {
        $this->assertNull($this->request->getPort());

        $this->request->setPort(443);
        $this->assertEquals(443, $this->request->getPort());
    }

    public function testAuthenticationMethod() {
        $this->assertEquals($this->request->getAuthenticationMethod(), Request::AUTHENTICATION_METHOD_BASIC);

        $this->request->setAuthenticationMethod(Request::AUTHENTICATION_METHOD_DIGEST);
        $this->assertEquals($this->request->getAuthenticationMethod(), Request::AUTHENTICATION_METHOD_DIGEST);

    }

    /**
     * @expectedException ride\library\validation\exception\ValidationException
     */
    public function testSetAuthenticationMethodThrowsExceptionWhenInvalidArgumentProvided() {
        $this->request->setAuthenticationMethod('test');
    }

    public function testUsername() {
        $this->assertNull($this->request->getUsername());

        $this->request->setUsername('ride-user');
        $this->assertEquals($this->request->getUsername(), 'ride-user');
    }

    public function testPassword() {
        $this->assertNull($this->request->getPassword());

        $this->request->setPassword('password');
        $this->assertEquals($this->request->getPassword(), 'password');
    }

    public function testFollowLocation() {
        $this->assertNull($this->request->willFollowLocation());

        $this->request->setFollowLocation(true);
        $this->assertTrue($this->request->willFollowLocation());
    }

}