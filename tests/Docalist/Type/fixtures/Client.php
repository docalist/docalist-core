<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */

namespace Docalist\Tests\Type\Fixtures;

use Docalist\Type\Entity;

class Client extends Entity {
    protected static function loadSchema() {
        return [
            'fields' => [
                'name' => [
                    'type' => 'Docalist\Type\Text',
                    'default' => 'noname'
                ],
                'factures' => [
                    'type' => 'Docalist\Tests\Type\Fixtures\Facture*',
                    'key' => 'code'
                ]
            ]
        ];
    }
}