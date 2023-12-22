<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */

// pas de namespace, la fonction docalist() est globale.

declare(strict_types=1);

use Docalist\Container\ContainerInterface;
use Docalist\Container\Exception\ServiceNotFoundException;
use Docalist\Kernel\Kernel;

/**
 * Retourne un service docalist.
 *
 * @template TService of object
 *
 * @return TService
 *
 * @throws InvalidArgumentException Si le service indiqué n'existe pas.
 */
/**
 * Retourne un service.
 *
 * @template TService
 *
 * @param class-string<TService>|string $id L'identifiant du service à retourner.
 *
 * @return ($id is class-string<TService> ? TService : bool|int|float|string|array<mixed>)
 *
 * @throws InvalidArgumentException Si le kernel n'est pas démarré ou si le container n'est pas créé.
 * @throws ServiceNotFoundException Si le service indiqué n'existe pas.
 */
function docalist(string $id = ContainerInterface::class): mixed
{
    return Kernel::getInstance()->getContainer()->get($id);
}
