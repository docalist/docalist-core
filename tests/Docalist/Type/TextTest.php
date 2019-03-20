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

use WP_UnitTestCase;
use Docalist\Type\Text;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TextTest extends WP_UnitTestCase
{
    public function testNew()
    {
        $a = new Text();
        $this->assertSame('', $a->getPhpValue());
    }

    /** @expectedException Docalist\Type\Exception\InvalidTypeException */
    public function testInvalidType()
    {
        new Text([]);
    }
}
