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
use SysEleven\IsilonEleven\Exceptions\IsilonNotFoundException;

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
     * List all or matching quotas. Can also be used to retrieve quota state from
     * existing reports. For any query argument not supplied, the default behavior
     * is return all.
     *
     * Possible parameters are:
     *
     * enforced=<boolean> Only list quotas with this enforcement (non-accounting).
     * persona=<string> Only list user or group quotas matching this persona (must be used with the corresponding
     * type argument).  Format is <PERSONA_TYPE>:<string/integer>, where PERSONA_TYPE is one of USER,GROUP, SID, ID, or GID.
     * zone=<string> Optional named zone to use for user and group resolution.
     * resume=<string> Continue returning results from previous call using this token (token should come from the
     * previous call, resume cannot be used with other options).
     * recurse_path_children=<boolean> If used with the path argument, match all quotas at that path or any descendent sub-directory.
     * resolve_names=<boolean> If true, resolve group and user names in personas.
     * recurse_path_parents=<boolean> If used with the path argument, match all quotas at that path or any parent directory.
     * include_snapshots=<boolean>Only list quotas with this setting for include_snapshots.
     * exceeded=<boolean> Set to true to only list quotas which have exceeded one or more of their thresholds.
     * path=<string> Only list quotas matching this path (see also recurse_path_*).
     * type=[directory|user|group|default-user|default-group] Only list quotas matching this type.
     * dir=[ASC|DESC] The direction of the sort.
     * report_id=<string> Use the named report as a source rather than the live quotas. See the /q/quota/reports resource for a list of valid reports.
     *
     * @param array $params
     * @return array|string
     * @throws \BadMethodCallException
     */
    public function listQuotas(array $params = [])
    {
        $valid = ['enforced', 'persona', 'zone', 'resume', 'recurse_path_children',
            'recurse_path_parents', 'resolve_names', 'include_snapshots', 'exceeded',
            'path', 'type', 'dir', 'report_id'];

        $use = [];
        foreach ($params AS $k => $v) {
            if (in_array($k, $valid, true)) {
                $use[$k] = $v;
            }
        }

        return $this->callApi('GET', '/platform/1/quota/quotas', ['query' => $use]);
    }

    /**
     * Returns the quota for the given id. if the quota is not found a IsilonNotFoundException is raised
     *
     * @param string $quotaId
     * @return array|bool|string
     * @throws \Exception
     */
    public function getQuota($quotaId)
    {
        if ($quotaId === '') {
            throw new \BadMethodCallException('You must provide a valid id for the quota to inquire');
        }

        $res = $this->callApi('GET', '/platform/1/quota/quotas/'.$quotaId);

        return $res['quotas'][0];
    }

    /**
     * Returns the quotas for the given path, if no quota for the given path is found an
     * IsilonNotFoundException is raised.
     *
     * @param string $path
     * @return array|string
     */
    public function getQuotaForPath($path = null)
    {
        if ($path === '') {
            throw new \BadMethodCallException('You must provide a path to search for');
        }

        $params = ['path' => $path];

        return $this->listQuotas($params);
    }

    /**
     * Creates a new quota for the given path and returns the id of the quota
     *
     * @param string $path
     * @param int $hard in bytes
     * @param int $soft in bytes
     * @param array|null $defaults
     * @return string
     * @throws \RuntimeException
     * @throws \BadMethodCallException
     */
    public function createQuota($path = null, $hard = null, $soft = null, array $defaults = null)
    {
        if (null === $defaults) {
            $defaults = $this->getQuotaDefaults();
        }

        if (null !== $hard) {
            $this->checkIsPositiveNumber($hard);
            $defaults['thresholds']['hard'] = $hard;
        }

        if (null !== $soft) {
            $this->checkIsPositiveNumber($soft);
            $defaults['thresholds']['soft'] = $soft;
        }

        if ('' === $path || $path === '/ifs/data' || $path === '/ifs') {
            throw new \BadMethodCallException('You must provide a valid path');
        }

        $defaults['path'] = $path;

        $res = $this->callApi('POST', '/platform/1/quota/quotas', ['json' => $defaults]);

        return $res['id'];
    }

    /**
     * @param $quotaId
     * @param int null $hard
     * @param int null $soft
     * @param int $grace Grace period in days
     * @return array|string
     * @throws \Exception
     */
    public function modifyQuota($quotaId, $hard = null, $soft = null, $grace = 7)
    {
        $this->getQuota($quotaId);

        $data = [];

        if (null !== $hard) {
            $this->checkIsPositiveNumber($hard);
            $data['hard'] = $hard;
        }

        if (null !== $soft) {
            $this->checkIsPositiveNumber($soft);
            $data['soft'] = $soft;
        }

        $this->checkIsPositiveNumber($grace);

        if (0 === count($data)) {
            throw new \BadMethodCallException('You must provide a value to change');
        }

        /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
        $data['soft_grace'] = $grace * 86400;

        $params = ['thresholds' => $data];

        return $this->callApi('PUT', '/platform/1/quota/quotas/'.$quotaId, ['json' => $params]);
    }

    /**
     * Deletes the given quota
     *
     * @param $quotaId
     * @return array|string
     */
    public function deleteQuota($quotaId)
    {
        $this->getQuota($quotaId);

        return $this->callApi('DELETE', '/platform/1/quota/quotas/'.$quotaId);
    }

    /**
     * @return array
     */
    public function getQuotaDefaults()
    {
        $defaults = [
            'container' => true,
            'enforced' => true,
            'force' => false,
            'include_snapshots' => false,
            'thresholds' => [
                'advisory' => null,
                'hard' => 10995116277760,
                'soft' => 9895604649984,
                'soft_grace' => 604800],
            'thresholds_include_overhead' => false,
            'type' => 'directory'];

        return $defaults;
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