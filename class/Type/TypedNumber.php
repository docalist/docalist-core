<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Type\TypedText;
use Docalist\Type\TableEntry;
use Docalist\Type\Text;

/**
 * Numéro typé : un type composite associant un type provenant d'une table d'autorité de type number à une
 * valeur de type texte : ISBN, DOI, Numéro de licence, numéro de sécu...
 *
 * La table associée contient une colonne format qui indique comment formatter les entrées.
 *
 * @property TableEntry $type   Type    Type de numéro.
 * @property Text       $value  Value   Numéro associé.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TypedNumber extends TypedText
{
    public static function loadSchema()
    {
        return [
            'label' => __('Numéro', 'docalist-core'),
            'description' => __('Numéro et type de numéro.', 'docalist-core'),
            'fields' => [
                'type' => [
                    'description' => __('Type de numéro', 'docalist-core'),
                ],
                'value' => [
                    'label' => __('Numéro', 'docalist-core'),
                    'description' => __('Numéro dans le format indiqué par le type.', 'docalist-core'),
                ],
            ],
        ];
    }

    public function getAvailableFormats()
    {
        return [
            'format' => __("Format indiqué dans la table d'autorité", 'docalist-core'),
        ] + parent::getAvailableFormats();
    }

    public function getFormattedValue($options = null)
    {
        $format = $this->getOption('format', $options, $this->getDefaultFormat());

        switch ($format) {
            case 'format':
                // Récupère le format indiqué dans la table
                $format = $this->type->getEntry('format') ?: $this->type->getPhpValue() . ' %s';

                // Si on n'a pas de format, on en construit un avec le libellé qui figure dans la table
                empty($format) && $format = $this->type->getEntryLabel() . ' %s';

                // Formatte le résultat
                return trim(sprintf($format, $this->formatField('value', $options)));
        }

        // Laisse la classe parent gérer les autres formats d'affichage disponibles
        return parent::getFormattedValue($options);
    }
}
