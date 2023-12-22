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

use Docalist\Container\ContainerBuilderInterface;
use Docalist\Container\ContainerInterface;

/**
 * Classe de base des extensions docalist.
 *
 * Fournit une implementation par défaut de KernelExtensionInterface (no-op) qui permet
 * aux classes descendantes de n'implémenter que les méthodes dont elles ont besoin.
 */
class KernelExtension implements KernelExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilderInterface $containerBuilder): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function boot(ContainerInterface $container): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function shutdown(): void
    {
    }
}
