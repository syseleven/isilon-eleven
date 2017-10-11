<?php
/**
 * isilon-eleven
 *
 * @package SysEleven\IsilonEleven\Exceptions
 * @author Markus Seifert <m.seifert@syseleven.de>
 */

namespace SysEleven\IsilonEleven\Exceptions;

use Throwable;

/**
 * Class IsilonConflictException
 * @package SysEleven\IsilonEleven\Exceptions
 */
class IsilonConflictException extends IsilonRunTimeException
{
    public function __construct($message, $code, $extraData = null, \Exception $previous = null)
    {
        if ($message === '') {
            $message = 'An object with the same properties exists';
        }

        parent::__construct($message, $code, $extraData, $previous);
    }
}