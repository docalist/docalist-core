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

namespace Docalist\Tests\Forms;

use Docalist\Forms\Hidden;
use Docalist\Test\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class HiddenTest extends DocalistTestCase
{
    public function testHasLayout(): void
    {
        $this->assertFalse($this->callNonPublic(new Hidden(), 'hasLayout'));
    }

    public function testGetAttributes(): void
    {
        $hidden = new Hidden();

        $this->assertSame(['type' => 'hidden'], $hidden->getAttributes());
    }
}
