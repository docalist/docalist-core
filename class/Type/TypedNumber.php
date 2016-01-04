<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Type;

use InvalidArgumentException;

/**
 * Nombre typé : un type composite associant un type provenant d'une table d'autorité
 * de type number à une valeur de type texte.
 * La table associée contient une colonne format qui indique comment formatter les
 * entrées.
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
                    'table' => 'table:numbers',
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
            'label' => __('Libellé indiqué dans la table suivi du numéro', 'docalist-core'),
            'v' => __('Numéro uniquement, sans aucune mention', 'docalist-core'),
            'v (t)' => __('Numéro suivi du type entre parenthèses', 'docalist-core'),
        ];
    }

    public function getFormattedValue($options = null)
    {
        $format = $this->getOption('format', $options, $this->getDefaultFormat());

        $type = $this->formatField('type', $options);
        $number = $this->formatField('value', $options);

        switch ($format) {
            case 'format':
                $format = $this->type->getEntry('format') ?: $this->type() . ' %s';

                return trim(sprintf($format, $number));

            // mal nommé, plutôt 't v'
            case 'label':   return trim($type . ' ' . $number); // insécable
            case 'v':       return $number;
            case 'v (t)':   return empty($type) ? $number : $number . ' ('  . $type . ')'; // espace insécable avant '('
        }

        throw new InvalidArgumentException("Invalid Number format '$format'");
    }
}
