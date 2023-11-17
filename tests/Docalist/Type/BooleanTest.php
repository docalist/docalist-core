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

use Docalist\Tests\DocalistTestCase;
use Docalist\Type\Boolean;
use Docalist\Type\Exception\InvalidTypeException;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BooleanTest extends DocalistTestCase
{
    public function testNew(): void
    {
        $a = new Boolean();
        $this->assertSame(true, $a->getPhpValue());
    }

    /** @expectedException Docalist\Type\Exception\InvalidTypeException */
    public function testInvalidType(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('expected boolean');

        new Boolean('ttt');
    }

    public function testToString(): void
    {
        $t = new Boolean(true);
        $f = new Boolean(false);

        $this->assertSame('true', $t->__toString());
        $this->assertSame('false', $f->__toString());
    }
}
