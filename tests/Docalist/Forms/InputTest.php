<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
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
