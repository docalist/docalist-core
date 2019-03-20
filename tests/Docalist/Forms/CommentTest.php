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
use Docalist\Forms\Comment;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
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
