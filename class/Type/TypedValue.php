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

namespace Docalist\Type;

use Docalist\Type\MultiField;
use Docalist\Type\TableEntry;
use Docalist\Type\Any;
use Docalist\Type\Collection\TypedValueCollection;

/**
 * Valeur typée : un type composite associant un champ TableEntry à une valeur.
 *
 * @property TableEntry $type   Type    Type.
 * @property Any        $value  Value   Valeur.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TypedValue extends MultiField
{
    public static function loadSchema(): array
    {
        return [
            'label' => __('Valeur typée', 'docalist-core'),
            'description' => __('Type et valeur.', 'docalist-core'),
            'fields' => [
                'type' => [
                    'type' => TableEntry::class,
                    'label' => __('Type', 'docalist-core'),
                    'table' => '',  // la table utilisée doit être indiquée par les classes descendantes
                ],
                'value' => [
                    'type' => Any::class,
                    'label' => __('Valeur', 'docalist-core'),
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function getCollectionClass(): string
    {
        return TypedValueCollection::class;
    }

    protected function getCategoryField(): TableEntry
    {
        return $this->type;
    }

    public function getAvailableFormats(): array
    {
        return [
            'v'     => __('Valeur', 'docalist-core'),
            'v (t)' => __('Valeur (Type)', 'docalist-core'),
            't : v' => __('Type : Valeur', 'docalist-core'),
            't: v'  => __('Type: Valeur', 'docalist-core'),
            't v'   => __('Type Valeur', 'docalist-core'),
        ];
    }

    public function getDefaultFormat(): string
    {
        return 't: v';
    }

    public function getFormattedValue($options = null)
    {
        $format = $this->getOption('format', $options, $this->getDefaultFormat());

        $type = $this->formatField('type', $options);
        $value = $this->formatField('value', $options);

        switch ($format) {
            case 'v':
                return $value;

            case 'v (t)': // Espace insécable avant la parenthèse ouvrante
                return empty($type) ? $value : $value . ' ('  . $type . ')';

            case 't : v': // Espace insécable avant le ':'
                return empty($type) ? $value : $type . ' : ' . $value;

            case 't: v':
                return empty($type) ? $value : $type . ': ' . $value;

            case 't v':
                return empty($type) ? $value : $type . ' ' . $value; // espace insécable
        }

        return parent::getFormattedValue($options);
    }

    public function filterEmpty(bool $strict = true): bool
    {
        // Supprime les éléments vides
        $empty = parent::filterEmpty();

        // Si tout est vide ou si on est en mode strict, terminé
        if ($empty || $strict) {
            return $empty;
        }

        // Retourne true si on n'a que le type et pas de valeur
        return $this->filterEmptyProperty('value');
    }
}
