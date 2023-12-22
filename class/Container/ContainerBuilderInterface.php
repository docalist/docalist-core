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
 * Un Service est un objet.
 *
 * @phpstan-type Service object
 *
 * Un ServiceFactory est un callable qui instancie un Service.
 * @phpstan-type ServiceFactory callable(ContainerInterface $container, string $id): object
 *
 * Un service peut être définit en passant à set() un Service ou un ServiceFactory.
 * @phpstan-type ServiceOrServiceFactory Service|ServiceFactory
 *
 * Un Listener est un callable qui est appellé lorsqu'un service est instancié.
 * @phpstan-type Listener callable(mixed, ContainerInterface, string): void
 *
 * Un ClassLoadListener est un callable qui est appellé lorsqu'une classe est chargée.
 * @phpstan-type ClassLoadListener callable(ContainerInterface $container, string $className): void
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface ContainerBuilderInterface
{
    /**
     * Valeur à passer à listen() pour écouter tous les services instanciés.
     */
    public const LISTEN_BEFORE_FACTORY_CALL = 'before';
    public const LISTEN_AFTER_FACTORY_CALL = 'after';

    /**
     * Définit un service.
     *
     * @param string                  $id      Identifiant du service.
     * @param ServiceOrServiceFactory $service Service ou callable qui retourne le service.
     *
     * @throws InvalidArgumentException S'il existe déjà un service ou un paramètre avec le même identifiant.
     */
    public function set(string $id, object|callable $service): static;

    /**
     * Remplace un service par un autre.
     *
     * - le service à remplacer doit exister
     * - les alias ne sont pas autorisés, le remplacement doit porter sur un vrai service
     *
     * @param string                  $id      Identifiant du service à remplacer.
     * @param ServiceOrServiceFactory $service Nouveau service ou callable qui retourne le nouveau service.
     *
     * @throws InvalidArgumentException Si le service indiqué n'existe pas.
     */
    public function replace(string $id, object|callable $service): static;

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
