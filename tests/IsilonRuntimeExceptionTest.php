<?php
/**
 * This file is part of the syseleven/mite-eleven package
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

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SysEleven\IsilonEleven\Exceptions\IsilonRunTimeException;


/**
 * IsilonRuntimeExceptionTest
 * @author C. Junge <c.junge@syseleven.de>
 * @package
 * @subpackage
 */
class IsilonRuntimeExceptionTest extends TestCase
{

    /**
     * @covers \SysEleven\IsilonEleven\Exceptions\IsilonRunTimeException::__construct
     * @covers \SysEleven\IsilonEleven\Exceptions\IsilonRunTimeException::getErrorData
     * @covers \SysEleven\IsilonEleven\Exceptions\IsilonRunTimeException::getResponse
     *
     */
    public function testConstruct()
    {
        $me = new IsilonRunTimeException('test', 123);

        $this->assertEquals('test', $me->getMessage());
        $this->assertEquals(123, $me->getCode());

        $me = new IsilonRunTimeException('', 123, new Response(500));

        $this->assertEquals('Isilon Error', $me->getMessage());
        $this->assertEquals(123, $me->getCode());
        $this->assertEquals(array(), $me->getErrorData());
        $this->assertInstanceOf(Response::class, $me->getResponse());
    }

}