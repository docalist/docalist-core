<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tools;

use Docalist\Tools\Tool;
use InvalidArgumentException;

/**
 * Interface d'une liste d'outils Docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Tools
{
    /**
     * Retourne la liste des outils disponibles.
     *
     * @return string[] Un tableau contenant le nom des outils disponibles.
     */
    public function getList(): array;

    /**
     * Teste si l'outil indiqué figure dans la liste des outils disponibles.
     *
     * @param string $name Nom de l'outil à tester.
     *
     * @return bool True si l'outil figure dans la liste, false sinon.
     */
    public function has(string $name): bool;

    /**
     * Retourne un outil.
     *
     * @param string $name Nom de l'outil à retourner.
     *
     * @throws InvalidArgumentException Si l'outil indiqué n'existe pas.
     *
     * @return Tool L'outil demandé.
     */
    public function get(string $name): Tool;
}
