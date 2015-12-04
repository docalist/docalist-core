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
namespace Docalist\Tests\Type;

use WP_UnitTestCase;
use Docalist\Type\Boolean;

class BooleanTest extends WP_UnitTestCase
{
    public function testNew()
    {
        $a = new Boolean();
        $this->assertSame(true, $a->value());
    }

    /** @expectedException Docalist\Type\Exception\InvalidTypeException */
    public function testInvalidType()
    {
        new Boolean('ttt');
    }

    public function testToString()
    {
        $t = new Boolean(true);
        $f = new Boolean(false);

        $this->assertSame('true', $t->__toString());
        $this->assertSame('false', $f->__toString());
    }
}
