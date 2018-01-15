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
use Docalist\Forms\Text;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
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
