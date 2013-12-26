<?php

namespace pallo\library\http\client;

use pallo\library\http\exception\HttpException;
use pallo\library\http\Header;
use pallo\library\http\HeaderContainer;
use pallo\library\log\Log;

/**
 * Abstract implementation of the HTTP client
 */
abstract class AbstractClient implements Client {

    /**
     * Source for the log messages
     * @var string
     */
    const LOG_SOURCE = 'http';

    /**
     * Name of the default authentication method
     * @var string
     */
    protected $authenticationMethod;

    /**
     * Default username for the requests
     * @var string
     */
    protected $username;

    /**
     * Default password for the requests
     * @var string
     */
    protected $password;

    /**
     * URL of the proxy server
     * @var string
     */
    protected $proxy;

    /**
     * Connection timeout in seconds
     * @var integer
     */
    protected $timeout = 10;

    /**
     * Instance of the log
     * @var pallo\library\log\Log
     */
    protected $log;

    /**
     * Sets the log
     * @param pallo\library\log\Log $log
     * @return null
     */
    public function setLog(Log $log) {
        $this->log = $log;
    }

    /**
     * Sets the authentication method
     * @param string $method Authentication method eg. Basic, Digest ...
     * @return null
     */
    public function setAuthenticationMethod($method) {
        $this->authenticationMethod = $method;
    }

    /**
     * Gets the authentication method
     * @return string
     */
    public function getAuthenticationMethod() {
        if (!$this->authenticationMethod) {
            return self::AUTHENTICATION_METHOD_BASIC;
        }

        return $this->authenticationMethod;
    }

    /**
     * Sets the default username
     * @param string $username
     * @return null
     */
    public function setUsername($username) {
        $this->username = $username;
    }

    /**
     * Gets the default username
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Sets the default password
     * @param string $password
     * @return null
     */
    public function setPassword($password) {
        $this->password = $password;
    }

    /**
     * Gets the default password
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Sets the proxy server
     * @param string $proxy URL of the proxy server
     * @return null
     */
    public function setProxy($proxy) {
        $this->proxy = $proxy;
    }

    /**
     * Gets the proxy server
     * @return string URL of the proxy server
     */
    public function getProxy() {
        return $this->proxy;
    }

    /**
     * Sets the connection timeout
     * @param integer $timeout Timeout in seconds
     * @return null
     */
    public function setTimeout($timeout) {
        $this->timeout = $timeout;
    }

    /**
     * Gets the connection timeout
     * @return integer Timeout in seconds
     */
    public function getTimeout() {
        return $this->timeout;
    }

    /**
     * Creates a header container from the provided headers
     * @param array $headers Header key-value pair
     * @return pallo\library\http\HeaderContainer
     */
    public function createHeaderContainer(array $headers = null) {
        $container = new HeaderContainer();

        if (!$headers) {
            return $container;
        }

        foreach ($headers as $header => $value) {
            $container->addHeader($header, $value);
        }

        if (!$container->hasHeader(Header::HEADER_USER_AGENT)) {
            $container->addHeader(Header::HEADER_USER_AGENT, 'PHP Pallo');
        }

        return $container;
    }

    /**
     * Creates a HTTP client request
     * @param string $method HTTP method (GET, POST, ...)
     * @param string $url URL for the request
     * @param pallo\library\http\HeaderContainer $headers Headers for the
     * request
     * @param string|array $body URL encoded string or an array of request
     * body arguments
     * @return pallo\library\http\client\Request
     */
    public function createRequest($method, $url, HeaderContainer $headers = null, $body = null) {
        if ($headers === null) {
            $headers = new HeaderContainer();
        }

        $vars = parse_url($url);

        if (isset($vars['path'])) {
            $path = $vars['path'];
        } else {
            $path = '/';
        }

        if (isset($vars['host'])) {
            $headers->setHeader(Header::HEADER_HOST, $vars['host'], true);
        }

        if (isset($vars['query'])) {
            $path .= '?' . $vars['query'];
        }

        $request = new Request($path, $method, 'HTTP/1.1', $headers, $body);

        if (isset($vars['port'])) {
            $request->setPort($vars['port']);
        }

        if (isset($vars['user'])) {
            $request->setUsername($vars['user']);
            $request->setPassword($vars['pass']);
            $request->setAuthenticationMethod($this->getAuthenticationMethod());
        } elseif ($this->username) {
            $request->setUsername($this->username);
            $request->setPassword($this->password);
            $request->setAuthenticationMethod($this->getAuthenticationMethod());
        }

        if (isset($vars['scheme']) && $vars['scheme'] == 'https') {
            $request->setIsSecure(true);
        }

        return $request;
    }

    /**
     * Performs a DELETE request to the provided URL
     * @param string $url URL of the request
     * @param string|array $body Body variables as a url encoded string or
     * an array with key value pairs
     * @param array $headers Array with the headers of the request
     * @return pallo\library\http\Response
     */
    public function delete($url, $body = null, array $headers = null) {
        $headers = $this->createHeaderContainer($headers);
        $request = $this->createRequest(Request::METHOD_DELETE, $url, $headers, $body);

        return $this->sendRequest($request);
    }

    /**
     * Performs a HEAD request to the provided URL
     * @param string $url URL of the request
     * @param array $headers Array with the headers of the request
     * @return pallo\library\http\Response
     */
    public function head($url, array $headers = null) {
        $headers = $this->createHeaderContainer($headers);
        $request = $this->createRequest(Request::METHOD_HEAD, $url, $headers);

        return $this->sendRequest($request);
    }

    /**
     * Performs a GET request to the provided URL
     * @param string $url URL of the request
     * @param array $headers Array with the headers of the request
     * @return pallo\library\http\Response
     */
    public function get($url, array $headers = null) {
        $headers = $this->createHeaderContainer($headers);
        $request = $this->createRequest(Request::METHOD_GET, $url, $headers);

        return $this->sendRequest($request);
    }

    /**
     * Performs a POST request to the provided URL
     * @param string $url URL of the request
     * @param string|array $body Body variables as a url encoded string or
     * an array with key value pairs
     * @param array $headers Array with the headers of the request
     * @return pallo\library\http\Response
     */
    public function post($url, $body = null, array $headers = null) {
        $headers = $this->createHeaderContainer($headers);
        $request = $this->createRequest(Request::METHOD_POST, $url, $headers, $body);

        return $this->sendRequest($request);
    }

    /**
     * Performs a PUT request to the provided URL
     * @param string $url URL of the request
     * @param string|array $body Body variables as a url encoded string or
     * an array with key value pairs
     * @param array $headers Array with the headers of the request
     * @return pallo\library\http\Response
     */
    public function put($url, $body = null, array $headers = null) {
        $headers = $this->createHeaderContainer($headers);
        $request = $this->createRequest(Request::METHOD_PUT, $url, $headers, $body);

        return $this->sendRequest($request);
    }

}