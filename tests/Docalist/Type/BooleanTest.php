<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Type;

use WP_UnitTestCase;
use Docalist\Type\Boolean;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BooleanTest extends WP_UnitTestCase
{
    public function testNew()
    {
        $a = new Boolean();
        $this->assertSame(true, $a->getPhpValue());
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
