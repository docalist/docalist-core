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

use WP_UnitTestCase;
use Docalist\Forms\Input;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class InputTest extends WP_UnitTestCase
{
    public function testConstruct()
    {
        $input = new Input();

        $this->assertSame(['type' => 'text'], $input->getAttributes());
    }
}
