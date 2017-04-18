<?php
/**
 * This file is part of the syseleven/isilon-eleven package
 * (c) SysEleven GmbH <info@syseleven.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author C. Junge <c.junge@syseleven.de>
 * @version 0.0.1
 * @package SysEleven\IsilonEleven
 */
namespace SysEleven\IsilonEleven;

use GuzzleHttp\Psr7\Request;

/**
 * Implementation of a simple interface to the mite time tracking api.
 *
 * @author C. Junge <c.junge@syseleven.de>
 * @version 0.0.1
 * @package SysEleven\IsilonEleven
 */
class IsilonClient implements IsilonInterface
{

    const ZONE_SYSTEM = 'System';
    const ZONE_S11CUSTOMERS = 'S11CUSTOMERS';

    /**
     * Rest client object
     *
     * @type RestClientInterface
     */
    protected $_rest;

    /**
     * IsilonClient constructor.
     *
     * @param RestClientInterface $rest
     */
    public function __construct(RestClientInterface $rest)
    {
        $this->_rest = $rest;
    }

    /**
     * Sets the rest client used to communicate with the api.
     *
     * @param RestClientInterface $rest
     * @return IsilonClient
     */
    public function setClient(RestClientInterface $rest)
    {
        $this->_rest = $rest;
        return $this;
    }

    /**
     * Returns the rest client
     *
     * @return RestClientInterface
     */
    public function getClient()
    {
        return $this->_rest;
    }

    /**
     * @param $handler
     */
    public function setHandler($handler)
    {
        $this->_rest->setHandler($handler);
    }

    /**
     * Get a summary of all the existent NFS shares on Isilon NAS
     *
     * @param string $zone  Specifies zone
     *
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     * @return array
     */
    public function listExports($zone = self::ZONE_S11CUSTOMERS)
    {
        return $this->callApi('GET', '/platform/1/protocols/nfs/exports?zone=' . $zone);
    }

    /**
     * Create a new NFS share on Isilon NAS
     *
     * @param array $paths    Paths of the share to create
     * @param string $zone    Zone which the newly created share should belong to
     *
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return array    An array containing the newly created export's id.
     */
    public function createExport(array $paths, $zone = self::ZONE_S11CUSTOMERS)
    {
        if (empty($paths)) {
            throw new \InvalidArgumentException('You need to specify at least one path for the new share.');
        }

        $params = [
            'paths' => $paths
        ];

        return $this->callApi('POST', '/platform/2/protocols/nfs/exports?zone=' . $zone, ['json' => $params]);
    }

    /**
     * Delete an NFS share
     *
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return array
     */
    public function deleteExport($id, $zone = self::ZONE_S11CUSTOMERS)
    {
        if (!is_numeric($id)) {
            throw new \InvalidArgumentException('Non-numeric export ID ' . $id . ' given.');
        }

        return $this->callApi('DELETE', '/platform/2/protocols/nfs/exports/' . $id . '?zone=' . $zone);
    }

    /**
     * Modify an existing NFS share
     *
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return array
     */
    public function updateExport($id, array $params)
    {
        if (!is_numeric($id)) {
            throw new \InvalidArgumentException('Non-numeric export ID ' . $id . ' given.');
        }

        return $this->callApi('PUT', '/platform/2/protocols/nfs/exports/' . $id, ['json' => $params]);
    }

    /**
     * Creates a directory if it doesn't exist.
     *
     * @param $path
     * @return array|string
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function createDirectory($path)
    {
        if (mb_substr($path, 0, 1) !== '/') {
            throw new \InvalidArgumentException('Use absolute paths starting with a slash.');
        }

        return $this->callApi(
            'PUT',
            '/namespace' . $path . '?overwrite=false',
            [],
            ['x-isi-ifs-target-type' => 'container']
        );
    }

    /**
     * Deletes a directory if empty, otherwise return error.
     *
     * @param $path
     * @return array|string
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function deleteDirectory($path)
    {
        // TODO maybe check if directory not empty
        if (mb_substr($path, 0, 1) !== '/') {
            throw new \InvalidArgumentException('Use absolute paths starting with a slash.');
        }

        return $this->callApi('DELETE', '/namespace' . $path);
    }

    /**
     * Prepares the $request object and sends it to call()
     *
     * @param string $method
     * @param string $path
     * @param array  $parameters
     * @param array  $headers
     *
     * @return array|string
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     */
    public function callApi($method, $path, array $parameters = [], array $headers = [])
    {
        if ("" === $path) {
            throw new \BadMethodCallException('No path provided.');
        }

        /** @var Request $request */
        $request = $this->getClient()->createRequest($method, $path, $headers);

        return $this->getClient()->call($request, $parameters);
    }

}