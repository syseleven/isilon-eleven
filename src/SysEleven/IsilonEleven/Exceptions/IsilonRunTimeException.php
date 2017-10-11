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
 * @package SysEleven\IsilonEleven
 */

namespace SysEleven\IsilonEleven\Exceptions;

use GuzzleHttp\Psr7\Response;

/**
 * IsilonRuntimeException, should be thrown if a customer object cannot be
 * found in the backend
 *
 * @author C. Junge <c.junge@syseleven.de>
 * @version 0.9.1
 * @package SysEleven\IsilonEleven
 */
class IsilonRunTimeException extends \Exception
{

    /**
     * Last http response
     *
     * @var Response
     */
    public $response;

    /**
     * Error data, only there if response returned a json response
     *
     * @var array
     */
    public $data;

    /**
     * Initializes the exception and sets the data, if message is an array it
     * will be stored in $data and a generic message will be set.
     *
     * @param string     $message
     * @param int        $code
     * @param null       $extraData
     * @param \Exception $previous
     */
    public function __construct($message, $code, $extraData = null, \Exception $previous = null)
    {
        $this->data = $extraData;

        if ($extraData instanceof Response) {
            $this->response = $extraData;

            $body = (string) $this->response->getBody();
            $this->data = [];

            $json = json_decode($body, true);
            if ($json) {
                $this->data = $json;
            }
        }

        if ($message === '') {
            $message = 'Isilon Error';
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the error data
     *
     * @return array
     */
    public function getErrorData()
    {
        return $this->data;
    }

    /**
     * Return the response if any
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

}