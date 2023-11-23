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

use Docalist\Forms\Reset;
use Docalist\Test\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ResetTest extends DocalistTestCase
{
    public function testGetType(): void
    {
        $this->assertSame('reset', (new Reset())->getAttribute('type'));
    }
}
