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
use SysEleven\IsilonEleven\Exceptions\IsilonConflictException;
use SysEleven\IsilonEleven\Exceptions\IsilonNotFoundException;
use SysEleven\IsilonEleven\Exceptions\IsilonRunTimeException;
use SysEleven\IsilonEleven\IsilonClient;
use \Mockery as m;
use SysEleven\IsilonEleven\RestClient;
use PHPUnit\Framework\TestCase;

/**
 * Test for IsilonEleven client library
 *
 * @package SysEleven\IsilonEleven\Tests
 */
class IsilonClientTest extends TestCase {

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
        $this->client = new IsilonClient($restClient, 'DUMMY');
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

        $exports = $this->client->createExport(['/test'], 'DUMMY');

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

    public function testListQuotas()
    {
        $client = $this->client;

        $client->setHandler(
            new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/quotas.json')),
                new Response(500)
            ])
        );

        $quotas = $this->client->listQuotas();

        static::assertTrue(is_array($quotas));
        static::assertArrayHasKey('quotas', $quotas);
        static::assertArrayHasKey('resume', $quotas);
    }

    public function testGetQuotaByPath()
    {
        $client = $this->client;

        $client->setHandler(
            new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/quota-by-path.json')),
            ])
        );

        $path = '/ifs/data/smith/smith2';
        $quota = $client->getQuotaForPath('/ifs/data/smith/smith2');

        file_put_contents(__DIR__.'/quota-by-path.json', \GuzzleHttp\json_encode($quota));

        static::assertArrayHasKey('quotas', $quota);
        static::assertCount(1, $quota['quotas']);
        static::assertEquals($path, $quota['quotas'][0]['path']);
    }

    public function testGetQuota()
    {
        $id = '4DG2AAEAAAAAAAAAAAAAQJYAAAAAAAAA';

        $handler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/quota.json')),
            new Response(400, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/not-found.json'))
        ]);

        $client = $this->getClient($handler);


        $quota = $client->getQuota($id);

        static::assertTrue(is_array($quota));
        static::assertArrayHasKey('path', $quota);

        try {
            $client->getQuota('BOGUS');

        } catch (IsilonNotFoundException $nf) {
            static::assertInstanceOf(IsilonNotFoundException::class, $nf);
        }
    }

    public function testCreateQuota()
    {
        $handler = new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], \GuzzleHttp\json_encode(['id' => 'QUOTA_NEW'])),
                new Response(409, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/conflict.json'))
            ]);

        $client = $this->getClient($handler);
        $res = $client->createQuota('/ifs/data/smith/smith2');

        static::assertNotNull($res);

        $quota = $client->getQuota($res);

        static::assertEquals('/ifs/data/smith/smith2', $quota['path']);

        try {
            $client->createQuota('/ifs/data/smith/smith2');

        } catch (IsilonConflictException $ce) {
            static::assertInstanceOf(IsilonConflictException::class, $ce);
        }
    }

    public function getClient(MockHandler $handler = null, $getConnection = false)
    {
        if ($getConnection === true) {
            $transport = new RestClient('https://blu-isilon-node1.syseleven.net:8080');
            $transport->setUsername('smith');
            $transport->setPassword('password_here');
            $client = new IsilonClient($transport, 'SMITH');

            return $client;
        }

        $client = $this->client;

        if (null !== $handler) {
            $client->setHandler($handler);
        }

        return $client;
    }

}