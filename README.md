# Ride: HTTP Client Library

HTTP client library of the PHP Ride framework.

## What's In This Library

### Client

The _Client_ interface lets you implement an HTTP client.

Out of the box, the cURL implementation is provided through the _CurlClient_ class

### Request

The _Request_ class adds authentication and other client options to the default HTTP request.

## Code Sample

Check the following code sample to see how this library should be used:

```
<?php

use ride\library\http\client\CurlClient;
use ride\library\http\client\Client;
use ride\library\http\HttpFactory;
use ride\library\log\Log;

function createHttpClient(HttpFactory $httpFactory, Log $log) {
    $client = new CurlClient($httpFactory);
    
    // optionally set a log, to follow the communication of the client
    $client->setLog($log);

    // set some basic options
    $client->setTimeout(3);
    $client->setFollowLocation(true);
    $client->setUserAgent('My-UserAgent');    

    // use authentication for the requests
    $client->setAuthenticationMethod('basic');
    $client->setAuthenticationMethod('digest');
    $client->setUsername('my-username');
    $client->setPassword('my-password');
    
    // use a proxy
    $client->setProxy('http://url.to/some-proxy);
    
    return $client;
}

function sendRequests(Client $client) {
    // shortcuts for simple requests
    $response = $client->get('http://www.google.com');
    $response = $client->get('http://www.google.com', array('Cache-Control' => 'no-cache'));
    $response = $client->head('http://www.google.com');
    $response = $client->head('http://www.google.com', array('Cache-Control' => 'no-cache'));
    
    // simple post request
    $response = $client->post('http://www.google.com');
    // with body
    $response = $client->post('http://www.google.com', array('q' => 'search string'));
    // and headers
    $response = $client->post('http://www.google.com', array('q' => 'search string'), array('Accept' => '*/*'));
    
    // the same for put and delete
    $response = $client->put('http://www.google.com');
    $response = $client->put('http://www.google.com', array('q' => 'search string'));
    $response = $client->put('http://www.google.com', array('q' => 'search string'), array('Accept' => '*/*'));
    $response = $client->delete('http://www.google.com', array('q' => 'search string'), array('Accept' => '*/*'));
    
    // you can create your own request and tune it before sending it out
    $request = $client->createRequest('https://www.google.com');
    $request->setFollowLocation($followLocation);
    $request->setAuthenticationMethod('basic');
    $request->setUsername('my-username');
    $request->setPassword('my-password');
    
    $response = $client->sendRequest($request);
    
    // handle response
    if ($response->isOk()) {
        $contentType = $response->getHeader('Content-Type');
        $body = $response->getBody();
    } else {
        $statusCode = $response->getStatusCode();
        switch ($statusCode) {
            case 403:
                // forbidden
                break;
            // and more
        }
    }
}
```

### Related Modules

You can check the following related modules of this library:

- [ride/lib-http](https://github.com/all-ride/ride-lib-http)

## Installation

You can use [Composer](http://getcomposer.org) to install this library.

```
composer require ride/lib-http-client
```
