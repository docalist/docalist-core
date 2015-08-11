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
 */
namespace Docalist\Core;

use Docalist\Type\Settings as TypeSettings;
use Docalist\Table\TableInfo;

/**
 * Config de Docalist Core.
 *
 * @property Docalist\Table\TableInfo[] $tables Liste des tables.
 */
class Settings extends TypeSettings {
    protected $id = 'docalist-core-settings';

    protected static function loadSchema() {
        return [
            'fields' => [
                'tables' => [
                    'type' => 'Docalist\Table\TableInfo*',
                    'key' => 'name',
                    'label' => __('Liste des tables d\'autorité personnalisées', 'docalist-core'),
                ]
            ]
        ];
    }
}