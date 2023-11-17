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
use Docalist\Type\Exception\InvalidTypeException;
use Docalist\Type\Integer;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class IntegerTest extends DocalistTestCase
{
    public function testNew(): void
    {
        $a = new Integer();
        $this->assertSame(0, $a->getPhpValue());
    }

    public function testInvalidType(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('expected int');

        new Integer('zzz');
    }
}
