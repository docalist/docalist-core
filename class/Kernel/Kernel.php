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

namespace Docalist\Kernel;

use Docalist\Container\Container;
use Docalist\Container\ContainerBuilder;
use Docalist\Container\ContainerBuilderInterface;
use Docalist\Container\ContainerInterface;
use InvalidArgumentException;

/**
 * Gère la liste des extensions docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Kernel implements KernelInterface
{
    /**
     * Liste des extensions.
     *
     * @var array<class-string<KernelExtensionInterface>,KernelExtensionInterface|null>
     */
    private static array $extensions = [];

    /**
     * Instance du kernel en cours.
     */
    private static Kernel|null $instance = null;

    /**
     * Etat du noyau.
     *
     * @var self::STATE_STOPPED|self::STATE_LOADED|self::STATE_BUILT|self::STATE_BOOTED
     */
    private int $state = self::STATE_STOPPED;

    /**
     * Instance du container.
     */
    public ContainerInterface|null $container = null;

    /**
     * @param class-string<KernelExtensionInterface> $className
     */
    public static function registerExtension(string $className): void
    {
        if (!is_null(self::$instance)) {
            throw new InvalidArgumentException('Kernel is already started');
        }

        if (array_key_exists($className, self::$extensions)) {
            throw new InvalidArgumentException(sprintf('Extension %s already registered', $className));
        }

        self::$extensions[$className] = null;
    }

    /**
     * Initialise le kernel.
     *
     * @param $environment Environnement en cours
     * @param $debug       Mode debug
     */
    public function __construct(private string $environment, private bool $debug)
    {
        if (!is_null(self::$instance)) {
            throw new InvalidArgumentException('Another Kernel is already running');
        }

        if (empty($this->environment)) {
            throw new InvalidArgumentException('Environment cannot be empty');
        }

        self::$instance = $this;
    }

    public function __destruct()
    {
        if ($this->state !== self::STATE_STOPPED) {
            $this->shutdown();
        }
        self::$instance = null;
    }

    public static function getInstance(): static
    {
        if (is_null(self::$instance)) {
            throw new InvalidArgumentException('Kernel is not started');
        }

        return self::$instance;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * {@inheritDoc}
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * {@inheritDoc}
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * {@inheritDoc}
     */
    public function load(): void
    {
        if ($this->state !== self::STATE_STOPPED) {
            throw new InvalidArgumentException('Kernel is already loaded');
        }

        foreach (self::$extensions as $className => &$extension) {
            $extension = new $className();
        }

        $this->state = self::STATE_LOADED;
    }

    /**
     * {@inheritDoc}
     */
    public function build(): void
    {
        if ($this->state !== self::STATE_LOADED) {
            $this->load();
        }

        $containerBuilder = $this->getContainerBuilder();
        foreach (self::$extensions as $extension) {
            // @phpstan-ignore-next-line
            $extension->build($containerBuilder);
        }

        $this->container = $containerBuilder->buildContainer($this);

        $this->state = self::STATE_BUILT;
    }

    /**
     * {@inheritDoc}
     */
    public function boot(): void
    {
        if ($this->state !== self::STATE_BUILT) {
            $this->build();
        }

        foreach (self::$extensions as $extension) {
            // @phpstan-ignore-next-line
            $extension->boot($this->container);
        }

        $this->state = self::STATE_BOOTED;
    }

    /**
     * {@inheritDoc}
     */
    public function shutdown(): void
    {
        if ($this->state === self::STATE_STOPPED) {
            throw new InvalidArgumentException('Already stopped');
        }

        foreach (self::$extensions as &$extension) {
            // @phpstan-ignore-next-line
            $extension->shutdown();
            $extension = null;
        }

        $this->state = self::STATE_STOPPED;
        $this->container = null;
    }

    private function getContainerBuilder(): ContainerBuilderInterface
    {
        $containerBuilder = new ContainerBuilder();

        return $containerBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function getContainer(): ContainerInterface
    {
        if (is_null($this->container)) {
            throw new InvalidArgumentException('Container is not built');
        }

        return $this->container;
    }
}
