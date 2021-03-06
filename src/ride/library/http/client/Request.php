<?php

namespace ride\library\http\client;

use ride\library\http\Request as HttpRequest;
use ride\library\validation\exception\ValidationException;

/**
 * Request for the HTTP client
 */
class Request extends HttpRequest {

    /**
     * Basic authentication method
     * @var string
     */
    const AUTHENTICATION_METHOD_BASIC = 'basic';

    /**
     * Digest authentication method
     * @var string
     */
    const AUTHENTICATION_METHOD_DIGEST = 'digest';

    /**
     * Port of the host
     * @var integer
     */
    protected $port;

    /**
     * Flag to see if the location header in the response should be followed
     * @var boolean
     */
    protected $followLocation;

    /**
     * Name of the authentication method
     * @var string
     */
    protected $authenticationMethod;

    /**
     * Username for the last created request
     * @var string
     */
    protected $username;

    /**
     * Password for the last created request
     * @var string
     */
    protected $password;

    /**
     * Sets the port of the host
     * @param integer $port
     * @return null
     */
    public function setPort($port) {
        $this->port = $port;
    }

    /**
     * Gets the port of the host
     * @return integer
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * Gets the URL to the server
     * @return string
     * @todo check for secure requests
     */
    public function getServerUrl() {
        $url = parent::getServerUrl();

        if ($this->port && ((!$this->isSecure && $this->port != 80) || ($this->isSecure && $this->port != 443))) {
            $url .= ':' . $this->port;
        }

        return $url;
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
        if ($method != self::AUTHENTICATION_METHOD_BASIC && $method != self::AUTHENTICATION_METHOD_DIGEST) {
            throw new ValidationException();
        }
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
     * Sets the username for this request
     * @param string $username
     * @return null
     */
    public function setUsername($username) {
        $this->username = $username;
    }

    /**
     * Gets the username for this request
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Sets the password for this request
     * @param string $password
     * @return null
     */
    public function setPassword($password) {
        $this->password = $password;
    }

    /**
     * Gets the password for this request
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

}
