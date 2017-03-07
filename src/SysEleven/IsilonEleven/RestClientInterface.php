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
use GuzzleHttp\Psr7\Request;

/**
 * Defines method for the rest client used by the api
 *
 * @author C. Junge <c.junge@syseleven.de>
 * @version 0.9.1
 * @package SysEleven\IsilonEleven
 */
interface RestClientInterface
{
    /**
     * Sets options for the connection adapter and the http client
     *
     * @param array $clientOptions
     *
     * @return IsilonClient
     */
    public function setClientOptions($clientOptions);

    /**
     * Returns the client options
     *
     * @return array
     */
    public function getClientOptions();


    /**
     * Set expected content type
     *
     * @param string $expectedContentType
     * @return IsilonClient
     */
    public function setExpectedContentType($expectedContentType);

    /**
     * Get expected content type
     *
     * @return string
     */
    public function getExpectedContentType();

    /**
     * @param object $handler
     * @return IsilonClient
     */
    public function setHandler($handler);

    /**
     * Set the password
     *
     * @param null $password
     * @return IsilonClient
     */
    public function setPassword($password);

    /**
     * Get the password
     *
     * @return string
     */
    public function getPassword();
    /**
     * Set username
     *
     * @param null $username
     * @return IsilonClient
     */
    public function setUsername($username);

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername();

    /**
     * Creates a new GuzzleHttp Client instance and returns it. You can overwrite
     * this method to fit your needs.
     *
     * @param array $options
     * @return mixed
     */
    public function getClient(array $options);

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
    public function createRequest($method = 'GET', $path, array $headers = []);

    /**
     * Make an API call
     *
     * @param \GuzzleHttp\Psr7\Request $request
     * @param array $params
     *
     * @return array|string
     */
    public function call(Request $request, array $params = []);

}