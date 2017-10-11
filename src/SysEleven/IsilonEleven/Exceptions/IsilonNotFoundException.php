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
 * Class IsilonNotFoundException
 * @package SysEleven\IsilonEleven\Exceptions
 */
class IsilonNotFoundException extends IsilonRunTimeException
{
    public function __construct($message = "", $code = 404, $extraData = null, Throwable $previous = null)
    {
        if ($message === '') {
            $message = 'The requested object was not found';
        }

        parent::__construct($message, $code, $extraData, $previous);
    }
}