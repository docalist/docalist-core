<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Tests\Type\Fixtures;

use Docalist\Type\Composite;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Money extends Composite {
    static public function loadSchema() {
        return [
            'label' => 'prix',
            'fields' => [
                'amount' => [
                    'type' => 'Docalist\Type\Decimal',
                    'default' => 0
                ],
                'currency' => [
                    'type' => 'Docalist\Type\Text',
                    'default' => 'EUR'
                ],
            ]
        ];
    }
}