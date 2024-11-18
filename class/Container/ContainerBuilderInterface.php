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

use Docalist\Kernel\KernelInterface;
use InvalidArgumentException;

/**
 * Un ContainerBuilder permet de créer le container Docalist.
 *
 * Le container peut contenir des services (des objets) et des paramètres (des valeurs).
 * @phpstan-type Service object
 * @phpstan-type Parameter bool|int|float|string|array<mixed>
 * @phpstan-type ServiceOrParameter Service|Parameter
 *
 * Un ServiceFactory est un callable qui instancie un service
 * @phpstan-type ServiceFactory callable(ContainerInterface $container, class-string $id): Service
 *
 * Un ParameterFactory est un callable qui instancie un paramètre
 * @phpstan-type ParameterFactory callable(ContainerInterface $container, string $id): Parameter
 *
 * Une liste de dépendances est un tableau qui liste le nom des services et des paramètres requis.
 * @phpstan-type DependencyList array<int,string>
 *
 * Un service peut être définit en passant à set() un Service, un ServiceFactory ou une liste de dépendances.
 * @phpstan-type ServiceDefinition Service|ServiceFactory|DependencyList
 *
 * Un Listener est un callable qui est appellé lorsqu'un service est instancié.
 * @phpstan-type Listener callable(mixed, ContainerInterface, string): void
 *
 * Un ClassLoadListener est un callable qui est appellé lorsqu'une classe est chargée.
 * @phpstan-type ClassLoadListener callable(ContainerInterface $container, string $className): void
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
// class Z {
//  /** @phpstan-param ServiceDefinition $x */
//  function test($x):void {
//     \PHPStan\dumpType($x);

//  }

// }

interface ContainerBuilderInterface
{
    /**
     * Id spécial à passer à listen() pour être appelé avant l'instanciation de n'importe quel service.
     */
    public const LISTEN_BEFORE_FACTORY_CALL = 'listen_before_instanciation';

    /**
     * Id spécial à passer à listen() pour être appelé après l'instanciation de n'importe quel service.
     */
    public const LISTEN_AFTER_FACTORY_CALL = 'listen_after_instanciation';

    /**
     * Définit un service.
     *
     * @template T
     *
     * @param class-string<T>|string                  $id      Identifiant du service.
     * @param ($id is class-string<T> ? Service|ServiceFactory|DependencyList : ParameterFactory)       $service Définition du service.
     *
     * @throws InvalidArgumentException S'il existe déjà un service avec le même id.
     */
    public function set(string $id, object|callable|array $service = []): static;

    /**
     * Remplace un service par un autre.
     *
     * - le service à remplacer doit exister
     * - les alias ne sont pas autorisés, le remplacement doit porter sur un vrai service
     *
     * @template T
     *
     * @param string                  $id      Identifiant du service à remplacer.
     * @param ($id is class-string<T> ? Service|ServiceFactory|DependencyList : ParameterFactory)       $service Définition du service.
     *
     * @throws InvalidArgumentException Si le service indiqué n'existe pas.
     */
    public function replace(string $id, object|callable|array $service): static;

    /**
     * Crée un alias pour un service ou un paramètre.
     *
     * @param string $alias Alias à créer
     * @param string $id    Id à utiliser
     */
    public function alias(string $alias, string $id): static;

    /**
     * Déprécie un service ou un paramètre.
     *
     * Un alias est automatiquement créé pour l'ancien id.
     * Un warning sera émis à chaque fois que l'ancien id est demandé à get() ou getParameter().
     */
    public function deprecate(string $old, string $new, string $since): static;

    /**
     * Enregistre un callable à appeller lorsque le service ou le paramètre indiqué est instancié.
     *
     * @template T
     *
     * @param class-string<T>                               $id
     * @param callable(T, ContainerInterface, string): void $listener
     */
    public function listen(string $id, callable $listener): static;

    /**
     * Enregistre un callable à appeller lorsqu'une classe particulière est chargée via l'autoload.
     *
     * @param class-string      $className
     * @param ClassLoadListener $listener
     */
    public function onClassLoad(string $className, callable $listener): static;

    /**
     * Construit et retourne le container.
     */
    public function buildContainer(KernelInterface $kernel): ContainerInterface;
}
