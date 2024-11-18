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
 * Permet aux extensions d'initialiser le container Docalist.
 *
 * @phpstan-import-type Service                 from ContainerBuilderInterface
 * @phpstan-import-type ServiceFactory          from ContainerBuilderInterface
 * @phpstan-import-type ParameterFactory        from ContainerBuilderInterface
 * @phpstan-import-type Listener                from ContainerBuilderInterface
 * @phpstan-import-type ClassLoadListener       from ContainerBuilderInterface
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ContainerBuilder implements ContainerBuilderInterface
{
    /**
     * Liste des services.
     *
     * @var array<string,Service|ServiceFactory|ParameterFactory>
     */
    private array $services = [];

    /**
     * Liste des alias.
     *
     * @var array<string,string> alias => id
     */
    private array $aliases = [];

    /**
     * Liste des id dépréciés.
     *
     * @var array<string,array<int,string>> old-id => [new-id, since]
     */
    private array $deprecated = [];

    /**
     * Liste des listeners.
     *
     * @var array<string,array<int,Listener>>
     */
    private array $listeners = [];

    /**
     * Callback a appeller quand des classes spécifiques sont chargées (autoload).
     *
     * @var array<class-string, array<ClassLoadListener>>
     */
    private array $autoloadListeners = [];

    /**
     * {@inheritDoc}
     */
    public function set(string $id, object|callable|array $service = []): static
    {
        if (array_key_exists($id, $this->services)) {
            throw new InvalidArgumentException(sprintf('Service "%s" already exists', $id));
        }

        if (is_array($service)) {
            $service = static fn (ContainerInterface $container) => new $id(...array_map(static fn (string $dependency) => $container->get($dependency), $service));
        }

        $this->services[$id] = $service;

        return $this;
    }

    // public function make(string $id, string ...$dependencies): static
    // {
    //     return $this->set(
    //         $id,
    //         fn (ContainerInterface $container) => new $id(...array_map(static fn (string $dependency) => $container->get($dependency), $dependencies))
    //     );
    // }

    /**
     * {@inheritDoc}
     */
    public function replace(string $id, object|callable|array $service): static
    {
        // Pas d'alias : on remplace un vrai service, pas un alias
        if (isset($this->aliases[$id])) {
            throw new InvalidArgumentException('Replacing an alias is not supported');
        }

        // Le service à remplacer doit exister
        if (!array_key_exists($id, $this->services)) {
            throw new InvalidArgumentException(sprintf('Service "%s" not found', $id));
        }

        // Supprime l'ancien service
        unset($this->services[$id]);

        // Définit le nouveau service
        $this->set($id, $service);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function alias(string $alias, string $id): static
    {
        if (isset($this->aliases[$alias])) {
            throw new InvalidArgumentException(sprintf(
                'Alias "%s" is already defined ("%s")',
                $alias,
                $this->aliases[$alias]
            ));
        }

        $this->aliases[$alias] = $id;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function deprecate(string $old, string $new, string $since): static
    {
        if (isset($this->deprecated[$old])) {
            throw new InvalidArgumentException(sprintf(
                'Service "%s" is already deprecated ("%s" since %s)',
                $old,
                $this->deprecated[$old][0],
                $this->deprecated[$old][1]
            ));
        }

        $this->alias($old, $new);
        $this->deprecated[$old] = [$new, $since];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function listen(string $id, callable $listener): static
    {
        if (isset($this->aliases[$id])) {
            throw new InvalidArgumentException('Listening on an alias is not supported');
        }
        if (!isset($this->listeners[$id])) {
            $this->listeners[$id] = [];
        }

        $this->listeners[$id][] = $listener;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function onClassLoad(string $className, callable $listener): static
    {
        $this->autoloadListeners[$className][] = $listener;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function buildContainer(KernelInterface $kernel): ContainerInterface
    {
        $isLoaded = [];

        $isLoaded[$kernel::class] = true;
        $this
            ->set($kernel::class, $kernel)
            ->alias(KernelInterface::class, $kernel::class)
            ->alias('kernel', KernelInterface::class);

        $autoloadListener = null;
        if (!empty($this->autoloadListeners)) {
            $autoloadListener = new AutoloadListener();
            $this->set(AutoloadListener::class, $autoloadListener);
            $isLoaded[AutoloadListener::class] = true;
        }

        $container = new Container($this->services, $this->aliases, $this->deprecated, $this->listeners, $isLoaded);

        if (!is_null($autoloadListener)) {
            $autoloadListener->register($container, $this->autoloadListeners);
        }

        return $container;
    }

    // /////////////////////////////////////// AUTOWIRING //////////////////////////////////////////

    // /**
    //  * @param class-string   $id
    //  * @param class-string[] $manualDependencies
    //  */
    // public function autowire(string $id, array $manualDependencies = []): static
    // {
    //     return $this->set($id, fn () => $this->resolve($id, $manualDependencies));
    // }

    // /**
    //  * @param class-string   $className
    //  * @param class-string[] $manualDependencies
    //  */
    // private function resolve(string $className, array $manualDependencies = []): object
    // {
    //     $reflectionClass = new ReflectionClass($className);

    //     $constructor = $reflectionClass->getConstructor();
    //     if (is_null($constructor)) {
    //         return new $className();
    //     }

    //     $parameters = $constructor->getParameters();
    //     if (empty($parameters)) {
    //         return new $className();
    //     }

    //     try {
    //         $arguments = [];
    //         foreach ($parameters as $parameter) {
    //             // Liaison manuelle
    //             $parameterName = $parameter->getName();
    //             if (array_key_exists($parameterName, $manualDependencies)) {
    //                 $dependency = $manualDependencies[$parameterName];

    //                 $arguments[] = $this->has($dependency) ? $this->get($dependency) : $this->getParameter($dependency);
    //                 continue;
    //             }

    //             if ($parameter->isDefaultValueAvailable()) {
    //                 break;
    //             }

    //             // Liaison automatique
    //             $type = $parameter->getType();
    //             if ($type instanceof ReflectionNamedType) {
    //                 $typeName = $type->getName();

    //                 // Nom de classe : recherche un service avec le nom de la classe
    //                 if (!$type->isBuiltin()) {
    //                     $arguments[] = $this->get($typeName);
    //                     continue;
    //                 }

    //                 // Entier ou chaine : recherche un paramètre avec le nom du paramètre
    //                 if ($typeName === 'string' || $typeName === 'int') {
    //                     if ($this->hasParameter($parameterName)) {
    //                         $arguments[] = $this->getParameter($parameterName);
    //                         continue;
    //                     }
    //                 }
    //             }

    //             // Type hint non géré (type scalaire, union type, intersection type, etc.)
    //             throw new InvalidArgumentException(sprintf(
    //                 'Unable to autowire dependency "%s" of service "%s", please provide a manual binding',
    //                 $parameterName,
    //                 $className
    //             ));
    //         }

    //         // Instancie le service
    //         return $reflectionClass->newInstanceArgs($arguments);
    //     } catch (Throwable $th) {
    //         throw new InvalidArgumentException(sprintf(
    //             'Unable to autowire service %s: %s',
    //             $className,
    //             $th->getMessage()
    //         ));
    //     }
    // }

    // /**
    //  * Supprime un service.
    //  */
    // public function remove(string $id): static
    // {
    //     unset($this->services[$id]);

    //     return $this;
    // }
}
