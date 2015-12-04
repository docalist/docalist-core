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
use Docalist\Type\Decimal;

class DecimalTest extends WP_UnitTestCase
{
    public function testNew()
    {
        $a = new Decimal();
        $this->assertSame(0.0, $a->value());

        $a = new Decimal(12);
        $this->assertSame(12., $a->value());
    }

    /** @expectedException Docalist\Type\Exception\InvalidTypeException */
    public function testInvalidType()
    {
        new Decimal('zzz');
    }
}
