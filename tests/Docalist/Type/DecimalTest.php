<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Tests\Type;

use WP_UnitTestCase;
use Docalist\Type\Decimal;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class DecimalTest extends WP_UnitTestCase
{
    public function testNew()
    {
        $a = new Decimal();
        $this->assertSame(0.0, $a->getPhpValue());

        $a = new Decimal(12);
        $this->assertSame(12., $a->getPhpValue());
    }

    /** @expectedException Docalist\Type\Exception\InvalidTypeException */
    public function testInvalidType()
    {
        new Decimal('zzz');
    }
}
