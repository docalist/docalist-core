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

class Facture extends Composite {
    protected static function loadSchema() {
        return [
            'fields' => [
                'code' => 'Docalist\Type\Text',
                'label' => 'Docalist\Type\Text',
                'total'  => 'Docalist\Type\Integer'
            ]
        ];
    }
}