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
use Docalist\Forms\Html;
use Docalist\Forms\Container;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class HtmlTest extends WP_UnitTestCase
{
    /**
     * Crée un containeur (mock).
     *
     * @return Container
     */
    protected function getContainer()
    {
        return $this->getMockForAbstractClass('Docalist\Forms\Container', func_get_args());
    }

    public function testGetSetContent()
    {
        $html = new Html();
        $this->assertNull($html->getParent());
        $this->assertNull($html->getContent());

        $html = new Html('test');
        $this->assertNull($html->getParent());
        $this->assertSame('test', $html->getContent());

        $html = new Html('');
        $this->assertNull($html->getParent());
        $this->assertNull($html->getContent()); // la chaine vide a été transformée en null

        $html = new Html(false);
        $this->assertNull($html->getParent());
        $this->assertNull($html->getContent()); // false a été transformée en null

        $parent = $this->getContainer('parent');
        $html = new Html('test', $parent);
        $this->assertSame('test', $html->getContent());
        $this->assertSame($parent, $html->getParent());

        $parent = $this->getContainer('parent');
        $html = new Html(null, $parent);
        $this->assertNull($html->getContent());
        $this->assertSame($parent, $html->getParent());
    }

    public function testDisplay()
    {
        $html = new Html('<p>Hello!</p>');
        $this->assertSame($html->getContent(), $html->render());
    }
}
