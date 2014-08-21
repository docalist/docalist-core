<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Tests\Type\Fixtures;

use Docalist\Type\Entity;

class Client extends Entity {
    protected static function loadSchema() {
        return [
            'name' => [ 'default' => 'noname' ],
            'factures' => ['type' => 'Facture*', 'key' => 'code']
        ];
    }
}