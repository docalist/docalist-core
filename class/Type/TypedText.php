<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Type\MultiField;
use Docalist\Type\TableEntry;
use Docalist\Type\Text;

/**
 * Texte typé : un type composite associant un champ TableEntry à une valeur de type Text.
 *
 * @property TableEntry $type   Type    Type de texte.
 * @property Text       $value  Value   Texte associé.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TypedText extends MultiField
{
    public static function loadSchema()
    {
        return [
            'label' => __('Texte', 'docalist-core'),
            'description' => __('Texte et type de texte.', 'docalist-core'),
            'fields' => [
                'type' => [
                    'type' => TableEntry::class,
                    'label' => __('Type', 'docalist-core'),
                    'table' => '',  // la table utilisée doit être indiquée par les classes descendantes
                ],
                'value' => [
                    'type' => Text::class,
                    'label' => __('Texte', 'docalist-core'),
                ],
            ],
        ];
    }

    public function getAvailableFormats()
    {
        return [
            'v'     => __('Valeur', 'docalist-core'),
            'v (t)' => __('Valeur (Type)', 'docalist-core'),
            't : v' => __('Type : Valeur', 'docalist-core'),
            't: v'  => __('Type: Valeur', 'docalist-core'),
            't v'   => __('Type Valeur', 'docalist-core'),
        ];
    }

    public function getDefaultFormat()
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

    public function filterEmpty($strict = true)
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
