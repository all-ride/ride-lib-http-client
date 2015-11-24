<?php

namespace ride\library\http\client;

use ride\library\http\Header;
use ride\library\http\HeaderContainer;
use ride\library\log\Log;

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
     * Default user agent
     * @var string
     */
    const DEFAULT_USER_AGENT = 'Ride';

    /**
     * Instance of the log
     * @var \ride\library\log\Log
     */
    protected $log;

    /**
     * Flag to see if the location header in the response should be followed
     * @var boolean
     */
    protected $followLocation;

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
     * Name of the user agent
     * @var string
     */
    protected $userAgent;

    /**
     * Sets the log
     * @param \ride\library\log\Log $log
     * @return null
     */
    public function setLog(Log $log) {
        $this->log = $log;
    }

    /**
     * Sets whether the location header in the response should be followed
     * @param boolean $followLocation
     * @return null
     */
    public function setFollowLocation($followLocation) {
        $this->followLocation = $followLocation;
    }

    /**
     * Gets whether the location header in the response should be followed
     * @return boolean
     */
    public function willFollowLocation() {
        return $this->followLocation;
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
     * Sets the user agent
     * @param string $userAgent
     * @return null
     */
    public function setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
    }

    /**
     * Gets the user agent
     * @return string
     */
    public function getUserAgent() {
        return $this->userAgent;
    }

    /**
     * Creates a header container from the provided headers
     * @param array $headers Header key-value pair
     * @return \ride\library\http\HeaderContainer
     */
    public function createHeaderContainer(array $headers = null) {
        $container = new HeaderContainer();

        if ($headers) {
            foreach ($headers as $header => $value) {
                $container->addHeader($header, $value);
            }
        }

        if (!$container->hasHeader(Header::HEADER_USER_AGENT)) {
            if (!$this->userAgent) {
                $this->userAgent = self::DEFAULT_USER_AGENT;
            }

            $container->setHeader(Header::HEADER_USER_AGENT, $this->userAgent);
        }

        return $container;
    }

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
    public function createRequest($method, $url, HeaderContainer $headers = null, $body = null) {
        if ($headers === null) {
            $headers = $this->createHeaderContainer();
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

        if ($this->followLocation) {
            $request->setFollowLocation(true);
        }

        return $request;
    }

    /**
     * Performs a DELETE request to the provided URL
     * @param string $url URL of the request
     * @param string|array $body Body variables as a url encoded string or
     * an array with key value pairs
     * @param array $headers Array with the headers of the request
     * @return \ride\library\http\Response
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
     * @return \ride\library\http\Response
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
     * @return \ride\library\http\Response
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
     * @return \ride\library\http\Response
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
     * @return \ride\library\http\Response
     */
    public function put($url, $body = null, array $headers = null) {
        $headers = $this->createHeaderContainer($headers);
        $request = $this->createRequest(Request::METHOD_PUT, $url, $headers, $body);

        return $this->sendRequest($request);
    }

}
