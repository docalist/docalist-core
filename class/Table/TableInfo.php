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

/**
 * Paramètres d'une table d'autorité.
 *
 * @property Text $name Nom de la table
 * @property Text $path Path (absolu) de la table
 * @property Text $label Libellé de la table
 * @property Text $format Format de la table (structure)
 * @property Text $type Type de la table (nature)
 * @property Boolean $readonly true : table prédéfinie, false : table utilisateur
 * @property Text $creation Date de création
 * @property Text $lastupdate Date de dernière modification
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
                    'type' => 'Docalist\Type\Text',
                    'label' => __('Nom', 'docalist-core'),
                    'description' => __('Nom de code de la table (doit être unique)', 'docalist-core'),
                ],

                'path' => [
                    'type' => 'Docalist\Type\Text',
                    'label' => __('Path', 'docalist-core'),
                    'description' => __('Path (absolu) de la table.', 'docalist-core'),
                ],

                'label' => [
                    'type' => 'Docalist\Type\Text',
                    'label' => __('Libellé', 'docalist-core'),
                    'description' => __('Libellé de la table', 'docalist-core'),
                ],

                'format' => [
                    'type' => 'Docalist\Type\Text',
                    'label' => __('Format', 'docalist-core'),
                    'description' => __('Format de la table (table, thesaurus, conversion, etc.)', 'docalist-core'),
                ],

                'type' => [
                    'type' => 'Docalist\Type\Text',
                    'label' => __('Type', 'docalist-core'),
                    'description' => __('Type de table (pays, langues, etc.)', 'docalist-core'),
                ],

                'readonly' => [
                    'type' => 'Docalist\Type\Boolean',
                    'default' => true,
                    'label' => __('Table en lecture seule', 'docalist-core'),
                    'description' => __("Indique si la table est prédéfinie ou personnalisée.", 'docalist-core'),
                ],

                'creation' => [
                    'type' => 'Docalist\Type\DateTime',
                    'label' => __('Date de création', 'docalist-core'),
                    'description' => __('Date/heure à laquelle la table a été créée.', 'docalist-core'),
                ],

                'lastupdate' => [
                    'type' => 'Docalist\Type\DateTime',
                    'label' => __('Dernière modification', 'docalist-core'),
                    'description' => __('Date/heure de dernière modification.', 'docalist-core'),
                ],
            ],
        ];
    }
}
