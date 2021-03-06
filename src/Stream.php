<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Http;

use GuzzleHttp\Psr7\Stream as Psr7Stream;
use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    use Traits\StreamDecoratorTrait;

    public function __construct($body = '', $options = [])
    {
        $this->stream = new Psr7Stream($body, $options);
    }
}
