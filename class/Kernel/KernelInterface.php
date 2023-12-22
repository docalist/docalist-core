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

use Docalist\Container\ContainerInterface;

/**
 * Le Kernel gère les extensions Docalist et le container.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface KernelInterface
{
    /** Le kernel est à l'arrêt (pas encore démarré ou stoppé) */
    public const STATE_STOPPED = 0;

    /** Le kernel et les extensions du kernel ont été instancées */
    public const STATE_LOADED = self::STATE_STOPPED + 1;

    /** Le container a été instancié et la méthode build() des extensions a été appellée. */
    public const STATE_BUILT = self::STATE_LOADED + 1;

    /** Le container est prêt, le kernel est démarré et la méthode boot() des extensions a été appellée. */
    public const STATE_BOOTED = self::STATE_BUILT + 1;

    /**
     * Retourne l'environnement en cours.
     */
    public function getEnvironment(): string;

    /**
     * Teste si le mode debug est activé.
     */
    public function isDebug(): bool;

    /**
     * Retourne l'état du kernel (l'une des constantes STATE_XXX).
     *
     * @return self::STATE_STOPPED|self::STATE_LOADED|self::STATE_BUILT|self::STATE_BOOTED
     */
    public function getState(): int;

    /**
     * Initialise le kernel et les extensions.
     */
    public function load(): void;

    /**
     * Instancie le container et appelle la méthode build() des extensions.
     */
    public function build(): void;

    /**
     * Démarre le kernel et appelle la méthode boot() des extensions.
     */
    public function boot(): void;

    /**
     * Appelle la méthode shutdown() des extensions et arrête le kernel.
     */
    public function shutdown(): void;

    /**
     * Retourne le container.
     */
    public function getContainer(): ContainerInterface;

    /**
     * Retourne le temps écoulé (en nanosecondes) depuis le démarrage du Kernel.
     *
     * @return int|float Retourne un entier sur les plateformes 64 bits, un float sur les plateformes 32 bits (cf. hrtime).
     */
    public function getElapsedTime(): int|float;
}
