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
class Facture extends Composite {
    static public function loadSchema() {
        return [
            'fields' => [
                'code' => 'Docalist\Type\Text',
                'label' => 'Docalist\Type\Text',
                'total'  => 'Docalist\Type\Integer'
            ]
        ];
    }
}