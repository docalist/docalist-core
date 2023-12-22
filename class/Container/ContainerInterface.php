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

namespace Docalist\Container;

use Docalist\Container\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Interface du container Docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface ContainerInterface // extends PsrContainerInterface
{
    /**
     * Retourne un service.
     *
     * @template TService
     *
     * @param class-string<TService>|string $id L'identifiant du service à retourner.
     *
     * @return ($id is class-string<TService> ? TService : bool|int|float|string|array<mixed>)
     *
     * @throws ServiceNotFoundException Si le service indiqué n'existe pas.
     */
    public function get(string $id): mixed;

    /**
     * Teste si un service existe.
     */
    public function has(string $id): bool;

    /**
     * Retourne le nom des services enregistrés dans le container.
     *
     * Les alias ne sont pas inclus dans la liste.
     *
     * @return array<int,class-string|string>
     */
    public function getServices(): array;

    /**
     * Indique si un service est instancié.
     */
    public function isLoaded(string $id): bool;

    /**
     * Alias de get(), retourne une chaine.
     */
    public function string(string $id): string;

    /**
     * Alias de get(), retourne un entier.
     */
    public function int(string $id): int;
}
