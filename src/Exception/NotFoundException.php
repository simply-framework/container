<?php

namespace Simply\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception that indicates an identifier that is not found in the container.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
