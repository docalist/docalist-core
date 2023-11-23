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

use Docalist\Forms\Text;
use Docalist\Test\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TextTest extends DocalistTestCase
{
    /** @return array<array<string>> */
    public static function textProvider(): array
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
    public function testDisplay(string $text, string $result): void
    {
        $text = new Text($text);
        $this->assertSame($result, $text->render());
    }
}
