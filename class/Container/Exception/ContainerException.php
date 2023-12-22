<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Container\Exception;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;

/**
 * Exception "service non trouvé".
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ContainerException extends InvalidArgumentException // implements ContainerExceptionInterface
{
}
