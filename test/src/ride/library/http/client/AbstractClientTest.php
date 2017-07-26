<?php

namespace ride\library\http\client;

use \PHPUnit_Framework_TestCase;
use ride\library\http\Request;

abstract class AbstractClientTest extends PHPUnit_Framework_TestCase {

    protected $client;

    public function testFollowLocation() {
        $this->assertFalse($this->client->willFollowLocation());

        $this->client->setFollowLocation(true);
        $this->assertTrue($this->client->willFollowLocation());
    }

    public function testCreateHeaderContainer() {
        $this->assertInstanceOf('ride\\library\\http\\HeaderContainer', $this->client->createHeaderContainer());
    }

    public function testFollowLocationActuallyFollowsLocation() {
        $url = 'http://www.google.com';

        $this->client->setFollowLocation(false);

        $response = $this->client->get($url);

        $this->assertFalse($response->isOk());
        $this->assertTrue($response->willRedirect());

        $this->client->setFollowLocation(true);

        $response = $this->client->get($url);

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->willRedirect());
    }

}
