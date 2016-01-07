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
use Docalist\Forms\Comment;

class CommentTest extends WP_UnitTestCase
{
    public function commentsProvider()
    {
        return [
            ['>'        , '<!-- > -->'], // must not start with a ">"
            ['->'       , '<!-- -> -->'], // must not start with a "->"
            ['--'       , '<!-- - - -->'], // must not contain "--"
            ['-'        , '<!-- - -->'], // must not end with "-".

            [''         , '<!-- -->'],
            ['test'     , '<!-- test -->'],

            ['-->'      , '<!-- - -> -->'],
            ['<!--'     , '<!-- <!- - -->'],
            ['<!---->'  , '<!-- <!- - - -> -->'],
        ];
    }

    /**
     * @dataProvider commentsProvider
     */
    public function testDisplay($comment, $result)
    {
        $comment = new Comment($comment);
        $this->assertSame($result, $comment->render());
    }
}
