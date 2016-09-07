<?php

namespace ride\library\http\client;

use \PHPUnit_Framework_TestCase;
use ride\library\http\Request;

class AbstractClientTest extends PHPUnit_Framework_TestCase {


    public function setUp() {
        $this->client = new TestClient();
    }

    public function testFollowLocation() {
        $this->assertNull($this->client->willFollowLocation());

        $this->client->setFollowLocation(true);
        $this->assertTrue($this->client->willFollowLocation());
    }

    public function testCreateHeaderContainer() {
        $this->assertInstanceOf('ride\\library\\http\\HeaderContainer', $this->client->createHeaderContainer());
    }

}