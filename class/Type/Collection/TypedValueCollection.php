<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type\Collection;

use Docalist\Type\Collection\MultiFieldCollection;
use Docalist\Type\Collection;
use Docalist\Type\TypedValue;
use Docalist\Type\Any;

/**
 * Une collection d'objets TypedValue.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TypedValueCollection extends MultiFieldCollection
{
    /**
     * Filtre les éléments de la collection sur le champ type des éléments et retourne les champ value.
     *
     * @param array $include    Liste des éléments à inclure (liste blanche) : si le tableau n'est pas vide, seuls les
     *                          éléments indiqués seront retournés.
     *
     * @param array $exclude    Liste des éléments à exclure (liste noire) : si le tableau n'est pas vide, les
     *                          éléments indiqués seront supprimés de la collection retournée.
     *
     * @param int   $limit      Nombre maximum d'éléments à retourner (0 = pas de limite).
     *
     * @return Collection
     */
    final public function filterValues(array $include = [], array $exclude = [], int $limit = 0): Collection
    {
        // Détermine la liste des éléments à retourner
        $items = [];
        foreach ($this->phpValue as $item) { /** @var TypedValue $item */
            // Filtre les eléments
            if (is_null($item = $this->filterItem($item, $include, $exclude))) {
                continue;
            }

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
