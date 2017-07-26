<?php

namespace ride\library\http\client;

use ride\library\http\HttpFactory;

class CurlClientTest extends AbstractClientTest {

    public function setUp() {
        $this->client = new CurlClient(new HttpFactory);
    }

}
