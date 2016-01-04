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

use Docalist\MappingBuilder;
use InvalidArgumentException;

/**
 * Texte typé : un type composite associant un type provenant d'une table d'autorité
 * à une valeur de type Text.
 */
class TypedText extends MultiField
{
    public static function loadSchema()
    {
        return [
            'label' => __('Texte', 'docalist-core'),
            'description' => __('Texte et type de texte.', 'docalist-core'),
            'editor' => 'table',
            'fields' => [
                'type' => [
                    'type' => 'Docalist\Type\TableEntry',
                    'label' => __('Type', 'docalist-core'),
                    'table' => '',
                ],
                'value' => [
                    'type' => 'Docalist\Type\Text',
                    'label' => __('Texte', 'docalist-core'),
                ],
            ],
        ];
    }

    public function setupMapping(MappingBuilder $mapping)
    {
        $name = $this->schema->name();
        $mapping->addField($name)->text();
        $mapping->addTemplate($name . '.*')->copyFrom($name)->copyDataTo($name);
    }

    public function mapData(array & $document)
    {
        $value = $this->__get('value')->value();
        if (empty($value)) {
            return;
        }

        $name = $this->schema->name();
        $type = $this->type();
        !empty($type) && $name .= '.' . $type;

        $repeatable = $this->schema->collection();

        $repeatable ? ($document[$name][] = $value) : ($document[$name] = $value);
    }

    public function getAvailableFormats()
    {
        return [
            'v' => __('Texte', 'docalist-core'),
            't : v' => __('Type : Texte', 'docalist-core'),
            't: v' => __('Type: Texte', 'docalist-core'),
            'v (t)' => __('Texte (Type)', 'docalist-core'),
        ];
    }

    public function getFormattedValue($options = null)
    {
        $format = $this->getOption('format', $options, $this->getDefaultFormat());

        $type = $this->formatField('type', $options);
        $text = $this->formatField('value', $options);

        switch ($format) {
            case 'v':       return $text;
            case 't : v':   return $type . ' : ' . $text; // espace insécable avant le ':'
            case 't: v':    return $type . ': ' . $text;
            case 'v (t)':   return empty($type) ? $text : $text . ' ('  . $type . ')'; // espace insécable avant '('
        }

        throw new InvalidArgumentException("Invalid TypedText format '$format'");
    }

    public function filterEmpty($strict = true)
    {
        // Supprime les éléments vides
        $empty = parent::filterEmpty();

        // Si tout est vide ou si on est en mode strict, terminé
        if ($empty || $strict) {
            return $empty;
        }

        // Retourne true si on n'a que le type de titre et pas de valeur
        return $this->filterEmptyProperty('value');
    }
}
