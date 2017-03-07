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

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use SysEleven\IsilonEleven\Exceptions\ApiNotAvailableException;
use SysEleven\IsilonEleven\Exceptions\AuthFailedException;
use SysEleven\IsilonEleven\Exceptions\IsilonRuntimeException;
use SysEleven\IsilonEleven\RestClient;

/**
 * Tests for IsilonEleven rest client library
 *
 * @package SysEleven\IsilonEleven\Tests
 */
class RestClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RestClient $object
     */
    public $restClient;

    public function setUp()
    {
        $this->restClient = new RestClient(null, null, array());
    }

    /**
     * @covers \SysEleven\IsilonEleven\RestClient::createRequest
     */
    public function testCreateRequest()
    {
        $this->restClient->setHandler(
            new MockHandler([
                new Response(200, ['X-Foo' => 'Bar', 'Content-Type' => 'application/json'], json_encode(['Test' => '123'])),
                new Response(202, ['Content-Length' => 0]),
            ])
        );

        $request = $this->restClient->createRequest('GET', '/testpath', array('Test' => 'MyTest'));
        $this->assertInstanceOf('\GuzzleHttp\Psr7\Request',$request);
        $this->assertEquals('MyTest',$request->getHeader('Test')[0]);
        $this->assertEmpty($request->getHeader('Content-Type'));

        $request = $this->restClient->createRequest('POST', '/testpath', array());
        $this->assertInstanceOf('\GuzzleHttp\Psr7\Request',$request);
        $this->assertEquals('application/json',$request->getHeader('Content-Type')[0]);
    }

    /**
     * @covers \SysEleven\IsilonEleven\RestClient::call
     */
    public function testCall()
    {
        $this->restClient->setHandler(
            new MockHandler([
                new Response(200, ['X-Foo' => 'Bar', 'Content-Type' => 'application/json'], json_encode(['Test' => '123'])),
                new Response(202, ['Content-Length' => 0]),
                new ConnectException('Error Communicating with Server', new Request('GET', 'test'))
            ])
        );

        // Regular get request
        $request = $this->restClient->createRequest('GET', '/testpath', ['test', 'test']);
        $result = $this->restClient->call($request);
        $this->assertEquals('123', $result['Test']);

        // Failed request
        try {
            $request = $this->restClient->createRequest('GET', '/testpath', ['test', 'test']);
            $result = $this->restClient->call($request);
            $this->assertEmpty($result);
            $this->assertFalse('Expected Test to throw Exception because condition should not be met');
        } catch (IsilonRuntimeException $e) {
            $this->assertEquals(2001, $e->getCode());
        }

        // Server problem
        try {
            $request = $this->restClient->createRequest('GET', '/testpath', ['test', 'test']);
            $this->restClient->call($request);
            $this->assertFalse('Expected Test to throw Exception because condition should not be met');
        } catch (ApiNotAvailableException $na) {
            $this->assertEquals('Error Communicating with Server', $na->getMessage());
        }

        $this->restClient->setHandler(
            new MockHandler([
                new Response(200, ['X-Foo' => 'Bar', 'Content-Type' => 'application/json'], 'Not JSON'),
                new Response(200, ['X-Foo' => 'Bar', 'Content-Type' => 'application/json'], ''),
                new Response(200, ['X-Foo' => 'Bar', 'Content-Type' => 'application/json'], json_encode([])),
            ])
        );

        // Returned data not JSON
        try {
            $this->restClient->setExpectedContentType('application/json');
            $request = $this->restClient->createRequest('GET', '/testpath');
            $this->restClient->call($request);
            $this->assertFalse('Expected Test to throw Exception because condition should not be met');
        } catch (IsilonRuntimeException $e) {
            $this->assertEquals('Cannot decode data', $e->getMessage());
        }

        // Empty response
        $request = $this->restClient->createRequest('GET', '/testpath');
        $result = $this->restClient->call($request);
        $this->assertTrue($result);

        // Empty array response
        $request = $this->restClient->createRequest('GET', '/testpath');
        $result = $this->restClient->call($request);
        $this->assertEmpty($result);

        $this->restClient->setHandler(
            new MockHandler([
                new Response(200, ['X-Foo' => 'Bar', 'Content-Type' => 'text/html'], 'Simple HTML'),
                new Response(403, ['X-Foo' => 'Bar', 'Content-Type' => 'application/json'], 'AuthFailed'),
            ])
        );

        // Simple HTML
        $this->restClient->setExpectedContentType('text/html');
        $request = $this->restClient->createRequest('GET', '/testpath');
        $result = $this->restClient->call($request);
        $this->assertEquals('Simple HTML', $result);

        // Returned data not JSON
        try {
            $this->restClient->setExpectedContentType('application/json');
            $request = $this->restClient->createRequest('GET', '/testpath');
            $this->restClient->call($request);
            $this->assertFalse('Expected Test to throw Exception because condition should not be met');
        } catch (AuthFailedException $e) {
            $this->assertEquals('AuthFailed', $e->getMessage());
            $this->assertEquals(403, $e->getCode());
        }
    }
}