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

    /**
     * Rest client object
     *
     * @type RestClientInterface
     */
    protected $_rest;

    /**
     * @var string
     */
    protected $defaultZone;

    /**
     * IsilonClient constructor.
     *
     * @param RestClientInterface $rest
     */
    public function __construct(RestClientInterface $rest, $defaultZone)
    {
        $this->_rest = $rest;
        $this->defaultZone = $defaultZone;
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
    public function listExports($zone = null)
    {
        if (null === $zone) {
            $zone = $this->defaultZone;
        }

        return $this->callApi('GET', '/platform/2/protocols/nfs/exports', ['zone' => $zone]);
    }

    /**
     * Get data of one share
     *
     * @param int $id Specifies share id
     * @param string $zone
     *
     * @return array
     * @throws \BadMethodCallException
     */
    public function getExport($id, $zone = null)
    {
        $this->checkIsPositiveNumber($id);

        if (null === $zone) {
            $zone = $this->defaultZone;
        }

        return $this->callApi('GET', '/platform/2/protocols/nfs/exports/' . $id, ['zone' => $zone])['exports'][0];
    }

    /**
     * Create a new NFS share on Isilon NAS
     *
     * @param array $paths          Paths of the share to create
     * @param string $zone          Zone which the newly created share should belong to
     * @param string $description   Description for newly created share
     *
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return array    An array containing the newly created export's id.
     */
    public function createExport(array $paths, $zone = null, $description = '')
    {
        if (empty($paths)) {
            throw new \InvalidArgumentException('You need to specify at least one path for the new share.');
        }

        if (in_array(null, $paths, true) || in_array('', $paths, true)) {
            throw new \InvalidArgumentException('Empty path element given.');
        }

        if (null === $zone) {
            $zone = $this->defaultZone;
        }

        $params = [
            'paths' => $paths,
            'description' => $description
        ];

        return $this->callApi('POST', '/platform/2/protocols/nfs/exports?zone=' . $zone, ['json' => $params]);
    }

    /**
     * Delete an NFS share
     *
     * @param $id
     * @param string $zone
     * @return array|string
     */
    public function deleteExport($id, $zone = null)
    {
        $this->checkIsPositiveNumber($id);

        if (null === $zone) {
            $zone = $this->defaultZone;
        }

        return $this->callApi('DELETE', '/platform/2/protocols/nfs/exports/' . $id . '?zone=' . $zone);
    }

    /**
     * Modify an existing NFS share
     *
     * @param $id
     * @param array $params
     * @param string $zone
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return array
     */
    public function updateExport($id, array $params, $zone = null)
    {
        $this->checkIsPositiveNumber($id);

        if (null === $zone) {
            $zone = $this->defaultZone;
        }

        return $this->callApi('PUT', '/platform/2/protocols/nfs/exports/' . $id . '?zone=' . $zone, ['json' => $params]);
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
        if (mb_substr($path, 0, 1) !== '/') {
            throw new \InvalidArgumentException('Use absolute paths starting with a slash.');
        }

        return $this->callApi('DELETE', '/namespace' . $path);
    }

    /**
     * Checks if directory exists
     *
     * @param $path
     *
     * @return bool
     * @throws \Exception
     */
    public function directoryExists($path)
    {
        if (mb_substr($path, 0, 1) !== '/') {
            throw new \InvalidArgumentException('Use absolute paths starting with a slash.');
        }

        try {
            $this->callApi('GET', '/namespace' . $path);
        } catch (\Exception $e) {
            if (404 === $e->getCode()) {
                return false;
            }

            throw $e;
        }

        return true;
    }

    /**
     * Check if share exists
     *
     * @param int $id Specifies share id
     *
     * @param string $zone
     * @return bool
     * @throws \Exception
     */
    public function exportExists($id, $zone = null)
    {
        $this->checkIsPositiveNumber($id);

        try {
            if (null === $zone) {
                $zone = $this->defaultZone;
            }

            $this->callApi('GET', '/platform/1/protocols/nfs/exports/' . $id, ['query' => ['zone' => $zone]]);
        } catch (\Exception $e) {
            if (404 === $e->getCode()) {
                return false;
            }

            throw $e;
        }

        return true;
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

    /**
     * @param $argument
     */
    private function checkIsPositiveNumber($argument) {
        if (!is_int($argument)) {
            throw new \InvalidArgumentException('Non-numeric export ID ' . $argument . ' given.');
        }

        if ($argument < 1) {
            throw new \InvalidArgumentException('Invalid export ID ' . $argument . ' given.');
        }
    }

    /**
     * @return string
     */
    public function getDefaultZone()
    {
        return $this->defaultZone;
    }

    /**
     * @param string $defaultZone
     * @return IsilonClient
     */
    public function setDefaultZone($defaultZone)
    {
        $this->defaultZone = $defaultZone;

        return $this;
    }


}