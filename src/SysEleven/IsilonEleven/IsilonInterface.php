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

/**
 * Defines a core set of methods for accessing the isilon services
 *
 * @author C. Junge <c.junge@syseleven.de>
 * @version 0.0.1
 * @package SysEleven\IsilonEleven
 */
interface IsilonInterface
{

    public function setClient(RestClientInterface $rest);

    public function getClient();

    public function listExports($zone = 'S11CUSTOMERS');

    public function createExport(array $paths, $zone = 'S11CUSTOMERS');

    public function deleteExport($id);

    public function updateExport($id, array $values);

    public function createDirectory($path);

    public function deleteDirectory($path);

    public function callApi($method, $path, array $parameters = [], array $headers = []);
}