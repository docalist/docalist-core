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

use Docalist\Forms\Checkbox;
use Docalist\Forms\Div;
use Docalist\Tests\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class DivTest extends DocalistTestCase
{
    public function testHasLayout(): void
    {
        $this->assertFalse($this->callNonPublic(new Div(), 'hasLayout'));
    }

    public function testHasDescriptionBlock(): void
    {
        $this->assertFalse($this->callNonPublic(new Div(), 'hasDescriptionBlock'));
    }
}