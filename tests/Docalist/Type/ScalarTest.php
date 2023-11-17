<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Tests\Type;

use Docalist\Type\Exception\InvalidTypeException;
use WP_UnitTestCase;
use Docalist\Type\Scalar;
use Docalist\Tests\DocalistTestCase;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ScalarTest extends DocalistTestCase
{
    public function testNew(): void
    {
        $a = new Scalar();
        $this->assertSame('', $a->getPhpValue());
    }

    /** @expectedException Docalist\Type\Exception\InvalidTypeException */
    public function testInvalidType(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('expected scalar');

        new Scalar([]);
    }

    public function testToString(): void
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
