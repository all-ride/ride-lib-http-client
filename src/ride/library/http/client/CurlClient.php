<?php

namespace ride\library\http\client;

use ride\library\http\exception\HttpException;
use ride\library\http\Cookie;
use ride\library\http\Header;
use ride\library\http\HttpFactory;
use ride\library\http\Request as LibraryRequest;

/**
 * cURL implementation of the HTTP client
 */
class CurlClient extends AbstractClient {

    /**
     * Handle of cURL
     * @var resource
     */
    protected $curl;

    /**
     * Flag to see if the location header in the response should be followed
     * @var boolean
     */
    protected $followLocation;

    /**
     * Flag to see if IPv4 should be forced
     * @var boolean
     */
    protected $forceIpv4;

    /**
     * Instance of the HTTP factory
     * @var \ride\library\http\HttpFactory
     */
    protected $factory;

    /**
     * Cookies received from previous requests
     * @var array
     */
    protected $cookies;

    /**
     * Constructs a new HTTP client
     * @return null
     * @throws \ride\library\http\exception\HttpException when cURL is not
     * available
     */
    public function __construct(HttpFactory $factory) {
        if (!function_exists('curl_init')) {
            throw new HttpException('Could not construct the client: cURL extension for PHP is not installed');
        }

        $this->factory = $factory;
        $this->cookies = array();
        $this->authenticationMethod = Request::AUTHENTICATION_METHOD_BASIC;
        $this->followLocation = false;
        $this->forceIpv4 = false;
    }

    /**
     * Destructs the HTTP client
     * @return null
     */
    public function __destruct() {
        if ($this->curl) {
            curl_close($this->curl);
        }
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
     * Sets whether IPv4 should be forced
     * @param boolean $forceIpv4
     * @return null
     */
    public function setForceIpv4($forceIpv4) {
        $this->forceIpv4 = $forceIpv4;
    }

    /**
     * Gets whether IPv4 should be forced
     * @return boolean
     */
    public function willForceIpv4() {
        return $this->forceIpv4;
    }

    /**
     * Performs a HTTP request
     * @param \ride\library\http\Request $request Request to send
     * @return \ride\library\http\Response Reponse of the request
     * @throws \ride\library\http\exception\HttpException
     */
    public function sendRequest(LibraryRequest $request) {
        if (!$this->curl) {
            $this->curl = curl_init();
        }

        $options = $this->getOptions($request);

        curl_setopt_array($this->curl, $options);

        // log the request
        if ($this->log) {
            $this->log->logDebug('Sending ' . ($request->isSecure() ? 'secure ' : '') . 'request', $request->getMethod() . ' ' . $request->getUrl(), self::LOG_SOURCE);
            // $this->log->logDebug('Options', var_export($options, true), self::LOG_SOURCE);

            if ($this->username) {
                $this->log->logDebug('Authorization', $request->getMethod() . ' ' . $this->username, self::LOG_SOURCE);
            }
        }

        // perform the request
        $responseString = curl_exec($this->curl);

        // check for errors
        $error = curl_error($this->curl);
        if ($error) {
            throw new HttpException($error);
        }

        $info = $this->updateRequestHeaders($request);

        $response = $this->factory->createResponseFromString($responseString);

        // log the response
        if ($this->log) {
           // $this->log->logDebug(var_export($info, true), null, self::LOG_SOURCE);
           $this->log->logDebug('Received response', $response->getStatusCode(), self::LOG_SOURCE);
        }

        // save the cookies for further requests
        $cookies = $response->getHeader(Header::HEADER_SET_COOKIE);
        if ($cookies) {
            if (!is_array($cookies)) {
                $cookies = array($cookies);
            }

            foreach ($cookies as $cookie) {
                $cookie = $this->factory->createCookieFromString($cookie, $request->getHeader(Header::HEADER_HOST));

                $this->registerCookie($cookie);
            }
        }

        return $response;
    }

    /**
     * Gets the cURL options for the provided request
     * @param \ride\library\http\Request $request
     * @return array
     * @throws \ride\library\http\exception\HttpException when an invalid
     * authentication method has been set
     */
    protected function getOptions(LibraryRequest $request) {
        $options = array(
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_HEADER => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLINFO_HEADER_OUT => true,
            // CURLOPT_VERBOSE => true,
        );

        if ($request instanceof Request) {
            if ($request->willFollowLocation()) {
                $options[CURLOPT_FOLLOWLOCATION] = true;
            }
        } elseif ($this->followLocation) {
            $options[CURLOPT_FOLLOWLOCATION] = true;
        }

        if ($request->isHead()) {
            $options[CURLOPT_NOBODY] = true;
            $options[CURLOPT_CUSTOMREQUEST] = null;
        } else {
            $options[CURLOPT_NOBODY] = false;
            $options[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
        }

        if ($this->forceIpv4 && defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
           $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }

        if ($this->proxy) {
            $options[CURLOPT_PROXY] = $this->proxy;
        }

        $headers = (string) $request->getHeaders();
        $headers = trim($headers);
        if ($headers) {
            $options[CURLOPT_HTTPHEADER] = explode("\r\n", $headers);

            if (!$request->getHeaders()->hasHeader('Expect')) {
                $options[CURLOPT_HTTPHEADER][] = 'Expect:';
            }
        } else {
            $options[CURLOPT_HTTPHEADER] = null;
        }

        $body = $request->getBody();
        $bodyParameters = $request->getBodyParametersAsString();
        if ($body) {
            $options[CURLOPT_POSTFIELDS] = $body;
        } elseif ($bodyParameters) {
            $options[CURLOPT_POSTFIELDS] = $bodyParameters;
        } else {
            $options[CURLOPT_POSTFIELDS] = null;
        }

        if ($request instanceof Request && $request->getUsername()) {
            $method = $request->getAuthenticationMethod();

            switch (strtolower($method)) {
            	case Request::AUTHENTICATION_METHOD_BASIC:
            	    $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;

            	    break;
            	case Request::AUTHENTICATION_METHOD_DIGEST:
            	    $options[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;

            	    break;
            	default:
            	    throw new HttpException('Could not send the request: invalid authentication method set (' . $method . ')');

            	    break;
            }

            $options[CURLOPT_USERPWD] = $request->getUsername() . ':' . $request->getPassword();
        } else {
            $options[CURLOPT_HTTPAUTH] = null;
            $options[CURLOPT_USERPWD] = null;
        }

        // set cookies
        $cookies = $this->getCookies($request);
        if ($cookies) {
            foreach ($cookies as $cookieIndex => $cookie) {
                $cookies[$cookieIndex] = $cookie->getName() . '=' . urlencode($cookie->getValue());
            }

            $options[CURLOPT_COOKIE] = implode('; ', $cookies);
        }

        return $options;
    }

    /**
     * Sets the actual sended headers to the request
     * @param \ride\library\http\Request $request
     * @return array Curl info
     */
    protected function updateRequestHeaders(LibraryRequest $request) {
        $info = curl_getinfo($this->curl);

        $lines = explode("\n", $info['request_header']);
        if (!$lines) {
            return $info;
        }

        array_shift($lines); // remove status code

        $headers = $request->getHeaders();
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) {
                continue;
            }

            list($header, $value) = explode(':', $line, 2);

            $headers->addHeader($header, trim($value));
        }

        return $info;
    }

    /**
     * Registers a cookie
     * @param \ride\library\http\Cookie $cookie
     * @return null
     */
    protected function registerCookie(Cookie $cookie) {
        $domain = $cookie->getDomain();

        $path = $cookie->getPath();
        if (!$path) {
            $path = '/';
        }

        if (!isset($this->cookies[$domain])) {
            $this->cookies[$domain] = array($path => array());
        } elseif (!isset($this->cookies[$domain][$path])) {
            $this->cookies[$domain][$path] = array();
        }

        $this->cookies[$domain][$path][$cookie->getName()] = $cookie;
    }

    /**
     * Gets the cookies for the provided request
     * @param \ride\library\http\Request $request
     * @return array
     * @see \ride\library\http\Cookie
     */
    protected function getCookies(Request $request) {
        $result = array();

        $isSecure = $request->isSecure();
        $domain = $request->getHeader(Header::HEADER_HOST);
        $domainLength = strlen($domain);
        $path = $request->getPath();
        $time = time();

        foreach ($this->cookies as $cookieDomain => $cookiePaths) {
            $domainPosition = strpos($domain, $cookieDomain);
            if ($domainPosition === false || $domainPosition != $domainLength - strlen($cookieDomain)) {
                continue;
            }

            foreach ($cookiePaths as $cookiePath => $cookies) {
                if (strpos($path, $cookiePath) !== 0) {
                    continue;
                }

                foreach ($cookies as $cookieName => $cookie) {
                    $expires = $cookie->getExpires();
                    if ($expires && $expires < $time) {
                        unset($this->cookies[$cookieDomain][$cookiePath][$cookieName]);

                        continue;
                    } elseif ($cookie->isSecure() && !$isSecure) {
                        continue;
                    }

                    $result[$cookie->getName()] = $cookie;
                }
            }
        }

        return $result;
    }

}
