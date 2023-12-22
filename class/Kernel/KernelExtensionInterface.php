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
 * Une extension docalist sert à paramétrer les services du container.
 */
interface KernelExtensionInterface
{
    /**
     * Initialise l'extension (une extension doit pouvoir être instanciée sans aucun paramètre).
     */
    public function __construct();

    /**
     * Cette méthode est appellée lors de l'initialisation du kernel pour permettre à
     * l'extension de définir des services et de paramétrer le container.
     */
    public function build(ContainerBuilderInterface $containerBuilder): void;

    /**
     * Cette méthode est appellée lorsque le kernel démarre.
     */
    public function boot(ContainerInterface $container): void;

    /**
     * Cette méthode est appellée lorsque le kernel s'arrête.
     */
    public function shutdown(): void;
}
