<?php

namespace Simply\Container\Exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * Base exception for all exceptions thrown from the container.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ContainerException extends \Exception implements ContainerExceptionInterface
{
}
