<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  HttpManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/httpmanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Http\Response;

use BiuradPHP\Http\Response;

/**
 * A class representing empty HTTP responses.
 */
class EmptyResponse extends Response
{
    /**
     * Create an empty response with the given status code.
     *
     * @param int $status Status code for the response, if any.
     * @param array $headers Headers for the response, if any.
     */
    public function __construct(int $status = 204, array $headers = [])
    {
        parent::__construct($status, $headers);
    }
}
