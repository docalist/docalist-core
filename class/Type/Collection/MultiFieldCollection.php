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
use Docalist\Type\MultiField;

/**
 * Une collection d'objets MultiField.
 *
 * @template Item of MultiField
 *
 * @extends Collection<Item>
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class MultiFieldCollection extends Collection
{
    /**
     * Indique si le filtre sur les éléments "internal*" est activé ou non (true par défaut).
     */
    private static bool $internalFilter = true;

    /**
     * Indique si le filtre sur les éléments "internal*" est activé ou non.
     */
    public function internalFilterIsEnabled(): bool
    {
        return self::$internalFilter;
    }

    /**
     * Active le filtre sur les éléments "internal*".
     *
     * Lorsque le filtre est activé, les méthodes filter() et similaires filtrent les éléments qui ont un
     * type de la forme "internal*".
     */
    public static function enableInternalFilter(): void
    {
        self::$internalFilter = true;
    }

    /**
     * Désactive le filtre sur les éléments "internal*".
     *
     * Lorsque le filtre est désactivé, les méthodes filter() et similaires ne filtrent pas les éléments qui ont un
     * type de la forme "internal*".
     */
    public static function disableInternalFilter(): void
    {
        self::$internalFilter = false;
    }

    /**
     * {@inheritDoc}
     */
    protected function filterItem(Any $item, array $include = [], array $exclude = []): ?Any
    {
        // On filtre par catégorie
        $value = $item->getCategoryCode();

        // Si on a une liste blanche et que l'item n'y figure pas, on l'ignore
        if (!empty($include) && !in_array($value, $include, true)) {
            return null;
        }

        // Si on a une liste noire et que l'item y figure, on l'ignore
        if (!empty($exclude) && in_array($value, $exclude, true)) {
            return null;
        }

        // Si c'est un item "internal" et que le filtre est activé, on l'ignore
        if (self::$internalFilter && strncmp($value, 'internal', 8) === 0) {
            return null;
        }

        // Ok
        return $item;
    }
}
