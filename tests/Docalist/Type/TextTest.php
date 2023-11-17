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
use Docalist\Type\Text;
use Docalist\Tests\DocalistTestCase;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TextTest extends DocalistTestCase
{
    public function testNew(): void
    {
        $a = new Text();
        $this->assertSame('', $a->getPhpValue());
    }

    public function testInvalidType(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('expected string');

        new Text([]);
    }
}
