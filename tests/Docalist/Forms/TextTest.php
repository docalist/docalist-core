<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Tests\Forms;

use WP_UnitTestCase;
use Docalist\Forms\Text;

class TextTest extends WP_UnitTestCase
{
    public function textProvider()
    {
        return [
            ['a', 'a'],
            ['é', 'é'],
            ['"', '"'],
            ["'", "'"],
            ['<', '&lt;'],
            ['>', '&gt;'],
        ];
    }

    /**
     * @dataProvider textProvider
     */
    public function testDisplay($text, $result)
    {
        $text = new Text($text);
        $this->assertSame($result, $text->render());
    }
}
