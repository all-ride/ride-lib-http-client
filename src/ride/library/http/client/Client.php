<?php

namespace ride\library\http\client;

use ride\library\http\HeaderContainer;
use ride\library\http\Request;

/**
 * Interface for a HTTP client
 */
interface Client {

    /**
     * Sets the authentication method
     * @param string $method Authentication method eg. Basic, Digest ...
     * @return null
     */
    public function setAuthenticationMethod($method);

    /**
     * Sets the proxy server
     * @param string $proxy URL of the proxy server
     * @return null
     */
    public function setProxy($proxy);

    /**
     * Sets the connection timeout
     * @param integer $timeout Timeout in seconds
     * @return null
     */
    public function setTimeout($timeout);

    /**
     * Creates a HTTP client request
     * @param string $method HTTP method (GET, POST, ...)
     * @param string $url URL for the request
     * @param \ride\library\http\HeaderContainer $headers Headers for the
     * request
     * @param string|array $body URL encoded string or an array of request
     * body arguments
     * @return \ride\library\http\client\Request
     */
    public function createRequest($method, $url, HeaderContainer $headers = null, $body = null);

    /**
     * Performs a request
     * @param \ride\library\http\Request $request Request to send
     * @return \ride\library\http\Response Response of the request
    */
    public function sendRequest(Request $request);

    /**
     * Performs a DELETE request to the provided URL
     * @param string $url URL of the request
     * @param string|array $body Body variables as a url encoded string or
     * an array with key value pairs
     * @param array $headers Array with the headers of the request
     * @return \ride\library\http\Response
     */
    public function delete($url, $body = null, array $headers = null);

    /**
     * Performs a HEAD request to the provided URL
     * @param string $url URL of the request
     * @param array $headers Array with the headers of the request
     * @return \ride\library\http\Response
     */
    public function head($url, array $headers = null);

    /**
     * Performs a GET request to the provided URL
     * @param string $url URL of the request
     * @param array $headers Array with the headers of the request
     * @return \ride\library\http\Response
     */
    public function get($url, array $headers = null);

    /**
     * Performs a POST request to the provided URL
     * @param string $url URL of the request
     * @param string|array $body Body variables as a url encoded string or
     * an array with key value pairs
     * @param array $headers Array with the headers of the request
     * @return \ride\library\http\Response
     */
    public function post($url, $body = null, array $headers = null);

    /**
     * Performs a PUT request to the provided URL
     * @param string $url URL of the request
     * @param string|array $body Body variables as a url encoded string or
     * an array with key value pairs
     * @param array $headers Array with the headers of the request
     * @return \ride\library\http\Response
     */
    public function put($url, $body = null, array $headers = null);

}