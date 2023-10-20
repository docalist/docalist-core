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

use Docalist\Forms\Container;
use Docalist\Forms\HtmlBlock;
use Docalist\Tests\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class HtmlBlockTest extends DocalistTestCase
{
    /**
     * Crée un containeur (mock).
     */
    protected function getContainer(string $name = ''): Container
    {
        return new Container($name);
    }

    public function testGetSetContent(): void
    {
        $html = new HtmlBlock();
        $this->assertNull($html->getParent());
        $this->assertSame('', $html->getContent());

        $html = new HtmlBlock('test');
        $this->assertNull($html->getParent());
        $this->assertSame('test', $html->getContent());

        $html = new HtmlBlock('');
        $this->assertNull($html->getParent());
        $this->assertSame('', $html->getContent()); // la chaine vide a été transformée en null

        // $html = new HtmlBlock(false);
        // $this->assertNull($html->getParent());
        // $this->assertNull($html->getContent()); // false a été transformée en null

        $parent = $this->getContainer('parent');
        $html = new HtmlBlock('test', $parent);
        $this->assertSame('test', $html->getContent());
        $this->assertSame($parent, $html->getParent());

        $parent = $this->getContainer('parent');
        $html = new HtmlBlock('', $parent);
        $this->assertSame('', $html->getContent());
        $this->assertSame($parent, $html->getParent());
    }

    public function testDisplay(): void
    {
        $html = new HtmlBlock('<p>Hello!</p>');
        $this->assertSame($html->getContent(), $html->render());
    }
}
