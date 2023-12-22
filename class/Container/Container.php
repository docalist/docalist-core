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
use Docalist\Services;

use function Docalist\deprecated;

/**
 * Gestionnaire de services Docalist.
 *
 * @phpstan-import-type ServiceOrServiceFactory from ContainerBuilderInterface
 * @phpstan-import-type Listener                from ContainerBuilderInterface
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Container implements ContainerInterface
{
    /**
     * @param array<class-string,ServiceOrServiceFactory> $services   Liste des services.
     * @param array<string,string>                        $aliases    Liste des alias (alias => id)
     * @param array<string,array<int,string>>             $deprecated Liste des id dépréciés (old-id => [new-id, since])
     * @param array<string,array<int,Listener>>           $listeners  Liste des listeners.
     * @param array<string,true>                          $isLoaded   Liste des id déjà instanciés.
     */
    public function __construct(
        private array $services,
        private array $aliases,
        private array $deprecated,
        private array $listeners,
        protected array $isLoaded = []
    ) {
        $this->services[ContainerInterface::class] = $this;
        $this->isLoaded[ContainerInterface::class] = true;
        $this->aliases['container'] = ContainerInterface::class;
        $this->aliases[Services::class] = ContainerInterface::class;
        $this->deprecated[Services::class] = [ContainerInterface::class, '2023-12-22'];
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $id): mixed
    {
        // Signale les services dépréciés
        if (isset($this->deprecated[$id])) {
            deprecated(
                sprintf('Service "%s"', $id),
                sprintf('"%s"', $this->deprecated[$id][0]),
                $this->deprecated[$id][1]
            );
        }

        // Résoud les alias
        if (isset($this->aliases[$id])) {
            return $this->get($this->aliases[$id]);
        }

        // Terminé si le service est déjà instancié
        if (isset($this->isLoaded[$id])) {
            // @phpstan-ignore-next-line
            return $this->services[$id];
        }

        // Exécute les listeners "BEFORE_FACTORY_CALL" (KernelDebugExtension par exemple)
        $this->callListeners($id, null, ContainerBuilderInterface::LISTEN_BEFORE_FACTORY_CALL);

        // Vérifie que le service a été défini
        if (!array_key_exists($id, $this->services)) {
            throw new ServiceNotFoundException(sprintf('Service "%s" not found', $id));
        }

        // Instancie le service
        $service = $this->services[$id];
        if (is_callable($service)) {
            $service = $this->services[$id] = $service($this, $id);
        }

        // Le service est chargé
        $this->isLoaded[$id] = true;

        // Exécute les listeners "AFTER_FACTORY_CALL" (KernelDebugExtension par exemple)
        $this->callListeners($id, $service, ContainerBuilderInterface::LISTEN_AFTER_FACTORY_CALL);

        // Exécute les listeners spécifiques au service instancé
        $this->callListeners($id, $service, $id);

        // @phpstan-ignore-next-line
        return $service;
    }

    private function callListeners(string $id, mixed $service, string $event): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }
        foreach ($this->listeners[$event] as $listener) {
            $listener($service, $this, $id);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $id): bool
    {
        while (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        return array_key_exists($id, $this->services);
    }

    /**
     * {@inheritDoc}
     */
    public function getServices(): array
    {
        return array_keys($this->services);
    }

    /**
     * {@inheritDoc}
     */
    public function isLoaded(string $id): bool
    {
        while (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        return isset($this->isLoaded[$id]);
    }

    /**
     * {@inheritDoc}
     */
    public function string(string $id): string
    {
        // @phpstan-ignore-next-line
        return $this->get($id);
    }

    /**
     * {@inheritDoc}
     */
    public function int(string $id): int
    {
        // @phpstan-ignore-next-line
        return $this->get($id);
    }
}
