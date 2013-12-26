<?php

namespace pallo\library\http\client;

use pallo\library\http\exception\HttpException;
use pallo\library\http\HttpFactory;
use pallo\library\http\Request as LibraryRequest;
use pallo\library\http\Response;

/**
 * cURL implementation of the HTTP client
 */
class CurlClient extends AbstractClient {

    /**
     * Flag to see if the location header in the response should be followed
     * @var boolean
     */
    protected $followLocation;

    /**
     * Instance of the HTTP factory
     * @var pallo\library\http\HttpFactory
     */
    protected $factory;

    /**
     * Constructs a new HTTP client
     * @return null
     * @throws pallo\library\http\exception\HttpException when cURL is not
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
     * @param pallo\library\http\Request $request Request to send
     * @return pallo\library\http\Response Reponse of the request
     */
    public function sendRequest(LibraryRequest $request) {
        $curl = curl_init();

        $options = array(
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_FOLLOWLOCATION => $this->followLocation,
            CURLOPT_HEADER => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
//             CURLOPT_VERBOSE => true,
//             CURLINFO_HEADER_OUT => true,
        );

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
        }

        $body = $request->getBody();
        $bodyParameters = $request->getBodyParametersAsString();
        if ($body) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        } elseif ($bodyParameters) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $bodyParameters);
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

        curl_setopt_array($curl, $options);

        if ($this->log) {
            $this->log->logDebug('Sending ' . ($request->isSecure() ? 'secure ' : '') . 'request', $request, self::LOG_SOURCE);
            $this->log->logDebug('Options', var_export($options, true), self::LOG_SOURCE);

            if ($this->username) {
                $this->log->logDebug('Authorization', $method . ' ' . $this->username, self::LOG_SOURCE);
            }
        }

        $responseString = curl_exec($curl);

        $error = curl_error($curl);
        if ($error) {
            throw new HttpException('cURL returned error: ' . $error);
        }

        if ($this->log) {
//             $this->log->logDebug(var_export(curl_getinfo($curl), true), null, self::LOG_SOURCE);
            $this->log->logDebug('Received response', $responseString, self::LOG_SOURCE);
        }

        curl_close($curl);

        return $this->factory->createResponseFromString($responseString);
    }

}