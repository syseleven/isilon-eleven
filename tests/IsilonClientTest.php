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

    public function tearDown()
    {
    }

    /**
     */
    public function testListExports()
    {
        $this->client->setHandler(
            new MockHandler([
                new Response(200, ['X-Foo' => 'Bar', 'Content-Type' => 'application/json'], json_encode(['Test' => '123'])),
            ])
        );

        $exports = $this->client->listExports();

        $this->assertEquals('123', $exports['Test']);
    }

    /**
     * @covers \SysEleven\IsilonEleven\IsilonClient::callApi
     * @expectedException \BadMethodCallException
     */
    public function testCallApi()
    {
    }
}