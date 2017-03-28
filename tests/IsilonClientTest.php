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
 * @package SysEleven\IsilonEleven\Tests
 */
namespace SysEleven\IsilonEleven\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use SysEleven\IsilonEleven\Exceptions\IsilonRunTimeException;
use SysEleven\IsilonEleven\IsilonClient;
use \Mockery as m;
use SysEleven\IsilonEleven\RestClient;

/**
 * Test for IsilonEleven client library
 *
 * @package SysEleven\IsilonEleven\Tests
 */
class IsilonClientTest extends \PHPUnit_Framework_TestCase {

    /**
     * Instance to be used for testing
     *
     * @var IsilonClient
     */
    protected $client;

    /**
     * Initializes the object
     */
    public function setUp()
    {
        $restClient = new RestClient('http://example.foo');
        $this->client = new IsilonClient($restClient);
    }

    /**
     */
    public function testListExport()
    {
        $this->client->setHandler(
            new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], json_encode(['Test' => '123'])),
            ])
        );

        $exports = $this->client->listExports();

        $this->assertEquals('123', $exports['Test']);
    }

    /**
     */
    public function testCreateExport()
    {
        $this->client->setHandler(
            new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], json_encode(['Test' => '123'])),
            ])
        );

        $exports = $this->client->createExport(['/test'], IsilonClient::ZONE_S11CUSTOMERS);

        $this->assertEquals('123', $exports['Test']);
    }

    /**
     */
    public function testUpdateExport()
    {
        $this->client->setHandler(
            new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], json_encode(['Test' => '123'])),
                new Response(500)
            ])
        );

        $export = $this->client->updateExport(1, ['somenew' => 'values']);
        $this->assertEquals('123', $export['Test']);

        try {
            $this->client->updateExport(4711, ['somenew' => 'values']);
            $this->assertFalse(true, 'Assertion should not be reachable');
        } catch (\Exception $e) {
            $this->assertInstanceOf(IsilonRunTimeException::class, $e);
        }

        try {
            $this->client->updateExport('Non numeric', ['somenew' => 'values']);
            $this->assertFalse(true, 'Assertion should not be reachable');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }
    }

    /**
     */
    public function testDeleteExport()
    {
        $this->client->setHandler(
            new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], json_encode(['Test' => '123'])),
                new Response(500)
            ])
        );

        $exports = $this->client->deleteExport(1);
        $this->assertEquals('123', $exports['Test']);

        try {
            $this->client->deleteExport(4711);
            $this->assertFalse(true, 'Assertion should not be reachable');
        } catch (\Exception $e) {
            $this->assertInstanceOf(IsilonRunTimeException::class, $e);
        }

        try {
            $this->client->deleteExport('Non numeric');
            $this->assertFalse(true, 'Assertion should not be reachable');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }
    }
}