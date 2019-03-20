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

namespace Docalist\Type\Interfaces;

use Docalist\Type\Collection;

/**
 * Interface d'une collection dont on peut filtrer les éléments.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Filterable
{
    /**
     * Filtre les éléments d'une collection en appliquant une liste blanche et/ou une liste noire.
     *
     * La méthode filtre les éléments de la collection d'origine en ne conservant que ceux qui figurent dans la
     * liste blanche et en excluant ceux qui figurent dans la liste noire.
     *
     * Elle retourne une nouvelle collection contenant les éléments obtenus (la collection d'origine n'est pas
     * modifiée).
     *
     * Le filtrage effectué sur les éléments dépend de la collection utilisée :
     *
     * - la classe Collection filtre sur la valeur de l'élément (telle que retournée par getPhpValue),
     * - la classe MultiFieldCollection filtre sur le type des éléments MultiField,
     * - etc.
     *
     * @param array $include    Liste des éléments à inclure (liste blanche) : si le tableau n'est pas vide, seuls
     *                          les éléments indiqués sont retournés.
     *
     * @param array $exclude    Liste des éléments à exclure (liste noire) : si le tableau n'est pas vide, les
     *                          éléments indiqués seront supprimés de la collection retournée.
     *
     * @param int   $limit      Nombre maximum d'éléments à retourner (0 = pas de limite).
     *
     * @return Collection
     */
    public function filter(array $include = [], array $exclude = [], int $limit = 0): Collection;
}
