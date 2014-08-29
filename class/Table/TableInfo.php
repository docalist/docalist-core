<?php
/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     $Id$
 */
namespace Docalist\Table;

use Docalist\Type\Object;
use Docalist\Type\String;
use Docalist\Type\Boolean;

/**
 * Paramètres d'une table d'autorité.
 *
 * @property String $name Nom de la table
 * @property String $path Path (absolu) de la table
 * @property String $label Libellé de la table
 * @property String $format Format de la table (structure)
 * @property String $type Type de la table (nature)
 * @property Boolean $user true : table utilisateur, false : table prédéfinie
 */
class TableInfo extends Object {
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

                'user' => [
                    'type' => 'bool',
                    'default' => true,
                    'label' => __('Table utilisateur', 'docalist-core'),
                    'description' => __("Indique s'il s'agit d'une table utilisateur ou d'une table prédéfinie.", 'docalist-core'),
                ]
            ]
        ];
        // @formatter:on
    }
}