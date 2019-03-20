<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tools;

/**
 * Interface d'un outil Docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Tool
{
    /**
     * Retourne le libellé de l'outil.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Retourne la description de l'outil.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Retourne la catégorie de l'outil.
     *
     * @return string
     */
    public function getCategory(): string;

    /**
     * Retourne la capacité requise pour pouvoir exécuter l'outil.
     *
     * @return string
     */
    public function getCapability(): string;

    /**
     * Exécute l'outil.
     *
     * @param array $args Paramètres.
     */
    public function run(array $args = []): void;
}
