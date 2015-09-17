<?php
/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Table
 * @author      Daniel Ménard <daniel.menard@laposte.net>
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
 */
class TableInfo extends Composite {
    // TODO : Gère l'ancien champ user, à enlever quand les settings auront ét éréenregistrés
    public function assign($value) {
        unset($value['user']);
        return parent::assign($value);
    }

    protected static function loadSchema() {
        // @formatter:off
        return [
            'fields' => [
                'name' => [
                    'label' => __('Nom', 'docalist-core'),
                    'description' => __('Nom de code de la table (doit être unique)', 'docalist-core'),
                ],

                'path' => [
                    'label' => __('Path', 'docalist-core'),
                    'description' => __('Path (absolu) de la table.', 'docalist-core'),
                ],

                'label' => [
                    'label' => __('Libellé', 'docalist-core'),
                    'description' => __('Libellé de la table', 'docalist-core'),
                ],

                'format' => [
                    'label' => __('Format', 'docalist-core'),
                    'description' => __('Format de la table (table, thesaurus, conversion, etc.)', 'docalist-core'),
                ],

                'type' => [
                    'label' => __('Type', 'docalist-core'),
                    'description' => __('Type de table (pays, langues, etc.)', 'docalist-core'),
                ],

                'readonly' => [
                    'type' => 'bool',
                    'default' => true,
                    'label' => __('Table en lecture seule', 'docalist-core'),
                    'description' => __("Indique s'il s'agit d'une table prédéfinie ou d'une table personnalisée.", 'docalist-core'),
                ],

                'creation' => [
                    'label' => __('Date de création', 'docalist-core'),
                    'description' => __("Date/heure à laquelle la table a été créée.", 'docalist-core'),
                ],

                'lastupdate' => [
                    'label' => __('Dernière création', 'docalist-core'),
                    'description' => __("Date/heure à laquelle la table a modifiée pour la dernière fois.", 'docalist-core'),
                ]
            ]
        ];
        // @formatter:on
    }
}