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

use Docalist\Forms\Comment;
use Docalist\Test\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class CommentTest extends DocalistTestCase
{
    /** @return  array<int, array<string>> */
    public static function commentsProvider(): array
    {
        return [
            ['>', '<!-- > -->'], // must not start with a ">"
            ['->', '<!-- -> -->'], // must not start with a "->"
            ['--', '<!-- - - -->'], // must not contain "--"
            ['-', '<!-- - -->'], // must not end with "-".

            ['', '<!-- -->'],
            ['test', '<!-- test -->'],

            ['-->', '<!-- - -> -->'],
            ['<!--', '<!-- <!- - -->'],
            ['<!---->', '<!-- <!- - - -> -->'],
        ];
    }

    /**
     * @dataProvider commentsProvider
     */
    public function testDisplay(string $comment, string $result): void
    {
        $comment = new Comment($comment);
        $this->assertSame($result, $comment->render());
    }
}
