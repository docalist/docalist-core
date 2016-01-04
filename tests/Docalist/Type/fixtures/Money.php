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

use Docalist\Type\Composite;

class Money extends Composite {
    static public function loadSchema() {
        return [
            'fields' => [
                'amount' => [
                    'type' => 'Docalist\Type\Decimal',
                    'default' => 0
                ],
                'currency' => [
                    'type' => 'Docalist\Type\Text',
                    'default' => 'EUR'
                ],
                'conversion' => 'Docalist\Tests\Type\Fixtures\Money*',
                'timestamp' => [
                    'type' => 'Docalist\Type\Integer'
                ]
            ]
        ];
    }
}