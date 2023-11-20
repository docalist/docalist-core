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

namespace Docalist\Type\Collection;

use Docalist\Type\Any;
use Docalist\Type\Collection;
use Docalist\Type\TypedValue;

/**
 * Une collection d'objets TypedValue.
 *
 * @template Item of TypedValue
 *
 * @extends MultiFieldCollection<Item>
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TypedValueCollection extends MultiFieldCollection
{
    /**
     * Filtre les éléments de la collection sur le champ type des éléments et retourne les champ value.
     *
     * @param array<string> $include liste des éléments à inclure (liste blanche) : si le tableau n'est pas vide, seuls
     *                               les éléments indiqués sont retournés
     * @param array<string> $exclude liste des éléments à exclure (liste noire) : si le tableau n'est pas vide, les
     *                               éléments indiqués seront supprimés de la collection retournée
     * @param int           $limit   nombre maximum d'éléments à retourner (0 = pas de limite)
     *
     * @return Collection<Any<mixed>>
     */
    final public function filterValues(array $include = [], array $exclude = [], int $limit = 0): Collection
    {
        // Détermine la liste des éléments à retourner
        $items = [];
        foreach ($this->phpValue as $item) {
            // Filtre les eléments
            $item = $this->filterItem($item, $include, $exclude);
            if (is_null($item)) {
                continue;
            }

            /** @var TypedValue $item */

            // Ajoute la valeur de l'élément à la liste
            $items[] = $item->value;

            // On s'arrête quand la limite est atteinte
            if ($limit && count($items) >= $limit) {
                break;
            }
        }

        // Crée une nouvelle collection contenant les éléments obtenus
        $result = new Collection([], $this->getSchema()); // les éléments qu'on retourne ne sont plus des TypedValue
        $result->phpValue = $items;

        // Ok
        return $result;
    }
}
