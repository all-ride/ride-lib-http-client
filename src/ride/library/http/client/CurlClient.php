<?php

namespace ride\library\http\client;

use ride\library\http\exception\HttpException;
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
     * Instance of the HTTP factory
     * @var \ride\library\http\HttpFactory
     */
    protected $factory;

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
        $this->authenticationMethod = Request::AUTHENTICATION_METHOD_BASIC;
        $this->followLocation = false;
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
     * Performs a HTTP request
     * @param \ride\library\http\Request $request Request to send
     * @return \ride\library\http\Response Reponse of the request
     */
    public function sendRequest(LibraryRequest $request) {
        if (!$this->curl) {
            $this->curl = curl_init();
        } else {
            curl_reset($this->curl);
        }

        curl_setopt_array($this->curl, $this->getOptions($request));

        // log the request
        if ($this->log) {
            $this->log->logDebug('Sending ' . ($request->isSecure() ? 'secure ' : '') . 'request', $request->getMethod() . ' ' . $request->getUrl(), self::LOG_SOURCE);
//             $this->log->logDebug('Options', var_export($options, true), self::LOG_SOURCE);

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
//            $this->log->logDebug(var_export($info, true), null, self::LOG_SOURCE);
            $this->log->logDebug('Received response', $response->getStatusCode(), self::LOG_SOURCE);
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
            CURLOPT_FOLLOWLOCATION => $this->followLocation,
            CURLOPT_HEADER => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLINFO_HEADER_OUT => true,
//            CURLOPT_VERBOSE => true,
        );

        if ($request->isHead()) {
            $options[CURLOPT_NOBODY] = true;
        } else {
            $options[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
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
            $options[CURLOPT_HTTPHEADER] = '';
        }

        $body = $request->getBody();
        $bodyParameters = $request->getBodyParametersAsString();
        if ($body) {
            $options[CURLOPT_POSTFIELDS] = $body;
        } elseif ($bodyParameters) {
            $options[CURLOPT_POSTFIELDS] = $bodyParameters;
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

}
