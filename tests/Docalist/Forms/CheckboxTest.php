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
use Docalist\Tests\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class CheckboxTest extends DocalistTestCase
{
    public function testHasDescriptionBlock(): void
    {
        $this->assertFalse($this->callNonPublic(new Checkbox(), 'hasDescriptionBlock'));
    }

    public function testGetAttributes(): void
    {
        $input = new Checkbox();

        $this->assertSame(['type' => 'checkbox', 'value' => 1], $input->getAttributes());
    }
}