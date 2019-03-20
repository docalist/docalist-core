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
use Docalist\Forms\HtmlBlock;
use Docalist\Forms\Container;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class HtmlBlockTest extends WP_UnitTestCase
{
    /**
     * Crée un containeur (mock).
     *
     * @return Container
     */
    protected function getContainer()
    {
        return $this->getMockForAbstractClass(Container::class, func_get_args());
    }

    public function testGetSetContent()
    {
        $html = new HtmlBlock();
        $this->assertNull($html->getParent());
        $this->assertNull($html->getContent());

        $html = new HtmlBlock('test');
        $this->assertNull($html->getParent());
        $this->assertSame('test', $html->getContent());

        $html = new HtmlBlock('');
        $this->assertNull($html->getParent());
        $this->assertNull($html->getContent()); // la chaine vide a été transformée en null

        $html = new HtmlBlock(false);
        $this->assertNull($html->getParent());
        $this->assertNull($html->getContent()); // false a été transformée en null

        $parent = $this->getContainer('parent');
        $html = new HtmlBlock('test', $parent);
        $this->assertSame('test', $html->getContent());
        $this->assertSame($parent, $html->getParent());

        $parent = $this->getContainer('parent');
        $html = new HtmlBlock(null, $parent);
        $this->assertNull($html->getContent());
        $this->assertSame($parent, $html->getParent());
    }

    public function testDisplay()
    {
        $html = new HtmlBlock('<p>Hello!</p>');
        $this->assertSame($html->getContent(), $html->render());
    }
}
