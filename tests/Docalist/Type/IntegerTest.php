<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Type;

use WP_UnitTestCase;
use Docalist\Type\Integer;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class IntegerTest extends WP_UnitTestCase
{
    public function testNew()
    {
        $a = new Integer();
        $this->assertSame(0, $a->getPhpValue());
    }

    /** @expectedException Docalist\Type\Exception\InvalidTypeException */
    public function testInvalidType()
    {
        new Integer('zzz');
    }
}
