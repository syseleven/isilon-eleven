<?php
/**
 * This file is part of the syseleven/isilon-eleven package
 * (c) SysEleven GmbH <info@syseleven.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author C. Junge <c.junge@syseleven.de>
 * @version 0.9.1
 * @package SysEleven\IsilonEleven
 */

namespace SysEleven\IsilonEleven;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;

use SysEleven\IsilonEleven\Exceptions\AuthFailedException;
use SysEleven\IsilonEleven\Exceptions\IsilonRunTimeException;
use SysEleven\IsilonEleven\Exceptions\ApiNotAvailableException;

/**
 * Rest client for accessing the api.
 *
 * @author C. Junge <c.junge@syseleven.de>
 * @version 0.9.1
 * @package SysEleven\IsilonEleven
 */
class RestClient implements RestClientInterface
{
    /**
     * Guzzle client instance
     *
     * @type
     */
    protected $_client;

    /**
     * Isilon username optional you should use the api key instead
     *
     * @type string
     */
    protected $_username = null;

    /**
     * Isilon password optional you should use the api key instead
     * @type string
     */
    protected $_password = null;

    /**
     * Host of your isilon account
     *
     * @type string
     */
    protected $_host = '';

    /**
     * User agent to pass to the api
     * @type string
     */
    protected $_agent = 'SysEleven Isilon Client 1.0';

    /**
     * Options for constructing the http client
     *
     * @type array
     */
    protected $_clientOptions = ['verify' => false];

    /**
     * Expected content types for the response
     *
     * @type array
     */
    protected $_expectedContentType = 'application/json';

    /**
     * Handler to be used by Guzzle
     *
     * @type array
     */
    protected $_handler = null;

    /**
     * Authentication Errors
     *
     * @type int
     */
    const AUTHENTICATION_ERROR = 403;

    /**
     * Encoding Error
     * @type int
     */
    const ENCODING_ERROR      = 2001;

    /**
     * Initializes the Client and sets some options.
     *
     * @param string $host   Url of your isilon account
     * @param string $key   Api key
     * @param array  $options
     */
    public function __construct($host, array $options = array())
    {
        $this->_host = $host;
        $this->_handler = new CurlHandler();

        $this->setOptions($options);
    }

    /**
     * Sets the options for the service, it first checks if a dedicated setter
     * for the key is available if not not it checks if there is a protected
     * property _key and then tries to find a property key, if key is not a
     * property of the class it is skipped (no overloading permitted here)
     *
     * @param array $options
     * @param array $ignoreSetters
     *
     * @return RestClientInterface
     */
    public function setOptions(array $options = array(), array $ignoreSetters = array())
    {
        if (is_array($options) && 0 != count($options)) {
            $ref = new \ReflectionClass($this);
            foreach ($options AS $k => $v) {
                if (is_numeric($k) || 0 == strlen($k)) {
                    continue;
                }

                // Look for a dedicated setter first, must be in the form
                // setCamelCasedKeyName
                $m = sprintf(
                    'set%s'
                    , str_replace(' ', '', ucwords(str_replace('_', ' ', $k)))
                );

                if ($ref->hasMethod($m) && !in_array(strtolower($m), $ignoreSetters)) {
                    $this->$m($v);
                    continue;
                }

                // Protected Variables are underscored by convention
                if ($ref->hasProperty('_' . $k)) {
                    $name = '_' . $k;
                    $this->$name = $v;
                    continue;
                }

                // camelCased
                $pro = '_'.str_replace(' ', '', ucwords(str_replace('_', ' ', $k)));
                $ft = strtolower(substr($pro,0,2));
                $pro = substr_replace($pro, $ft, 0, 2);

                if ($ref->hasProperty($pro)) {
                    $this->$pro = $v;
                    continue;
                }
            }
        }

        return $this;
    }

    /**
     * Sets the isilon api host address
     *
     * @param $host
     *
     * @return RestClientInterface
     */
    public function setHost($host)
    {
        $this->_host = $host;

        return $this;
    }

    /**
     * Gets the isilon api host address
     *
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Sets options for the adapter
     *
     * @param array $clientOptions
     *
     * @return RestClientInterface
     */
    public function setClientOptions($clientOptions)
    {
        $this->_clientOptions = $clientOptions;

        return $this;
    }

    /**
     * Retunrs the client options
     *
     * @return array
     */
    public function getClientOptions()
    {
        return $this->_clientOptions;
    }


    /**
     * Set expected content type
     *
     * @param string $expectedContentType
     * @return RestClientInterface
     */
    public function setExpectedContentType($expectedContentType)
    {
        $this->_expectedContentType = $expectedContentType;

        return $this;
    }

    /**
     * Get expected content type
     *
     * @return string
     */
    public function getExpectedContentType()
    {
        return $this->_expectedContentType;
    }

    /**
     * Set the password
     *
     * @param null $password
     * @return RestClientInterface
     */
    public function setPassword($password)
    {
        $this->_password = $password;

        return $this;
    }

    /**
     * Get the password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Set username
     *
     * @param null $username
     * @return RestClientInterface
     */
    public function setUsername($username)
    {
        $this->_username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->_username;
    }

    /**
     * @return null|object
     */
    public function getHandler()
    {
        return $this->_handler;
    }

    /**
     * @param object $handler
     * @return RestClient
     */
    public function setHandler($handler)
    {
        $this->_handler = $handler;
        return $this;
    }

    /**
     * Creates a new Guzzle Http Client instance and returns it. You can overwrite
     * this method to fit your needs.
     *
     * @param array $options
     * @return \GuzzleHttp\Client
     */
    public function getClient(array $options)
    {
        $stack = HandlerStack::create($this->_handler);

        $options = array_merge(['handler' => $stack], $options);

        $this->_client = new Client($options);

        return $this->_client;
    }

    /**
     * Creates a new Request object and returns it
     *
     * @param string $method
     * @param string $path
     * @param array  $headers
     *
     * @throws \RuntimeException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function createRequest($method = 'GET', $path, array $headers = array())
    {
        if (!in_array(
            $method, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD'], true
        )
        ) {
            throw new \RuntimeException('Method ' . $method . ' not supported');
        }

        $_headers = [];
        if (!in_array($method, ['GET', 'DELETE'], true)) {
            $_headers['Content-Type'] = 'application/json';
        }

        $_headers['Accept'] = 'application/json';

        $headers = array_merge_recursive($_headers, $headers);

        return new Request($method, sprintf('%s%s', $this->_host, $path), $headers);
    }

    /**
     * Make a API call
     *
     * @param Request $request
     * @param array $params
     *
     * @return \GuzzleHttp\Psr7\Stream|mixed|\Psr\Http\Message\StreamInterface
     *
     * @throws ApiNotAvailableException
     * @throws AuthFailedException
     * @throws IsilonRunTimeException
     */
    public function call(Request $request, array $params = [])
    {
        if (null !== $this->_username && null !== $this->_password) {
            $this->_clientOptions['auth'] = [$this->_username, $this->_password];
        }

        $client = $this->getClient($this->_clientOptions);

        try {
            /**
             * @var \GuzzleHttp\Psr7\Response $response
             */
            $response = $client->send($request, $params);

        }
        catch (ConnectException $ce) {
            throw new ApiNotAvailableException($ce->getMessage(), $ce->getCode());
        }
        catch (ClientException $ce) {
            if (403 === $ce->getResponse()->getStatusCode()) {
                throw new AuthFailedException($ce->getResponse()->getBody(),
                    self::AUTHENTICATION_ERROR,
                    $ce->getResponse());
            }
            throw new IsilonRunTimeException($ce->getMessage(),
                $ce->getCode(),
                null, $ce);
        }
        catch (\Exception $e) {
            throw new IsilonRunTimeException($e->getMessage(),
                $e->getCode(),
                null, $e);
        }

        $contents = $response->getBody()->getContents();
        $statuscode = $response->getStatusCode();

        if ($statuscode >= 200 && $statuscode < 300) {
            $type = $response->getHeader('Content-Type');

            if (empty($type)) {
                $message = 'No content-type header in response.';

                throw new IsilonRunTimeException($message,
                    self::ENCODING_ERROR,
                    $response);
            }

            if (preg_match('/^application\/json/', $type[0])) {
                if ('' === trim($contents)) {
                    return true;
                }

                $data = json_decode($contents, true);

                if (!is_array($data)) {
                    $message = 'Cannot decode data';

                    throw new IsilonRunTimeException($message,
                        self::ENCODING_ERROR,
                        $response);
                }

                return $data;
            }

            if (preg_match('/^text\/plain/', $type[0])) {
                return $contents;
            }

            return $response->getBody();
        }

        $type = $response->getHeader('Content-Type');

        if (!empty($type) && preg_match('/^application\/json/', $type[0])) {
            $data = json_decode($response->getBody(), true);
            throw new IsilonRunTimeException($data,
                $response->getStatusCode());
        }

        throw new IsilonRunTimeException($response->getBody(),
            $response->getStatusCode(),
            $response);
    }
}