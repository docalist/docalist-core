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

namespace Docalist\Tests\Type;

use WP_UnitTestCase;

use Docalist\Type\Integer;

class IntegerTest extends WP_UnitTestCase {
    public function testNew() {
        $a = new Integer();
        $this->assertSame(0, $a->value());
    }

    /** @expectedException Docalist\Type\Exception\InvalidTypeException */
    public function testInvalidType()
    {
        new Integer('12');
    }
}