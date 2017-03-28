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
use SysEleven\IsilonEleven\Exceptions\IsilonRuntimeException;


/**
 * IsilonRuntimeExceptionTest
 * @author C. Junge <c.junge@syseleven.de>
 * @package
 * @subpackage
 */
class IsilonRuntimeExceptionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \SysEleven\IsilonEleven\Exceptions\IsilonRuntimeException::__construct
     * @covers \SysEleven\IsilonEleven\Exceptions\IsilonRuntimeException::getErrorData
     * @covers \SysEleven\IsilonEleven\Exceptions\IsilonRuntimeException::getResponse
     *
     */
    public function testConstruct()
    {
        $me = new IsilonRuntimeException('test', 123);

        $this->assertEquals('test', $me->getMessage());
        $this->assertEquals(123, $me->getCode());

        $me = new IsilonRuntimeException(array(), 123, new Response(500));

        $this->assertEquals('Isilon Error', $me->getMessage());
        $this->assertEquals(123, $me->getCode());
        $this->assertEquals(array(), $me->getErrorData());
        $this->assertInstanceOf(Response::class, $me->getResponse());
    }

}