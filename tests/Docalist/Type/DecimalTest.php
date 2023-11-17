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
use Docalist\Type\Decimal;
use Docalist\Type\Exception\InvalidTypeException;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class DecimalTest extends DocalistTestCase
{
    public function testNew(): void
    {
        $a = new Decimal();
        $this->assertSame(0.0, $a->getPhpValue());

        $a = new Decimal(12);
        $this->assertSame(12., $a->getPhpValue());
    }

    public function testInvalidType(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('expected float');

        new Decimal('zzz');
    }
}
