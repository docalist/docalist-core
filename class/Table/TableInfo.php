<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Table;

use Docalist\Type\Composite;
use Docalist\Type\Text;
use Docalist\Type\Boolean;
use Docalist\Type\DateTime;

/**
 * Paramètres d'une table d'autorité.
 *
 * @property Text       $name       Nom de la table
 * @property Text       $path       Path (absolu) de la table
 * @property Text       $label      Libellé de la table
 * @property Text       $format     Format de la table (structure)
 * @property Text       $type       Type de la table (nature)
 * @property Boolean    $readonly   true : table prédéfinie, false : table utilisateur
 * @property DateTime   $creation   Date de création
 * @property DateTime   $lastupdate Date de dernière modification
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TableInfo extends Composite
{
    public static function loadSchema()
    {
        return [
            'fields' => [
                'name' => [
                    'type' => Text::class,
                    'label' => __('Nom', 'docalist-core'),
                    'description' => __('Nom de code de la table (doit être unique)', 'docalist-core'),
                ],

                'path' => [
                    'type' => Text::class,
                    'label' => __('Path', 'docalist-core'),
                    'description' => __('Path (absolu) de la table.', 'docalist-core'),
                ],

                'label' => [
                    'type' => Text::class,
                    'label' => __('Libellé', 'docalist-core'),
                    'description' => __('Libellé de la table', 'docalist-core'),
                ],

                'format' => [
                    'type' => Text::class,
                    'label' => __('Format', 'docalist-core'),
                    'description' => __('Format de la table (table, thesaurus, conversion, etc.)', 'docalist-core'),
                ],

                'type' => [
                    'type' => Text::class,
                    'label' => __('Type', 'docalist-core'),
                    'description' => __('Type de table (pays, langues, etc.)', 'docalist-core'),
                ],

                'readonly' => [
                    'type' => Boolean::class,
                    'default' => true,
                    'label' => __('Table en lecture seule', 'docalist-core'),
                    'description' => __("Indique si la table est prédéfinie ou personnalisée.", 'docalist-core'),
                ],

                'creation' => [
                    'type' => DateTime::class,
                    'label' => __('Date de création', 'docalist-core'),
                    'description' => __('Date/heure à laquelle la table a été créée.', 'docalist-core'),
                ],

                'lastupdate' => [
                    'type' => DateTime::class,
                    'label' => __('Dernière modification', 'docalist-core'),
                    'description' => __('Date/heure de dernière modification.', 'docalist-core'),
                ],
            ],
        ];
    }
}
