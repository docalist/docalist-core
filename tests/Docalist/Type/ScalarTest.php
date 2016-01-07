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
use Docalist\Type\Scalar;

class ScalarTest extends WP_UnitTestCase
{
    public function testNew()
    {
        $a = new Scalar();
        $this->assertSame('', $a->value());
    }

    /** @expectedException Docalist\Type\Exception\InvalidTypeException */
    public function testInvalidType()
    {
        new Scalar([]);
    }

    public function testToString()
    {
        $this->assertSame('true', (new Scalar(true))->__toString());
        $this->assertSame('false', (new Scalar(false))->__toString());

        $this->assertSame('0', (new Scalar(0.0))->__toString());
        $this->assertSame('3.14', (new Scalar(3.14))->__toString());

        $this->assertSame('0', (new Scalar(0))->__toString());
        $this->assertSame('1', (new Scalar(1))->__toString());

        $this->assertSame('""', (new Scalar(''))->__toString());
        $this->assertSame('"abc"', (new Scalar('abc'))->__toString());
    }
}
