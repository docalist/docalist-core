<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Forms;

use WP_UnitTestCase;
use Docalist\Forms\Item;
use Docalist\Forms\Input;
use Docalist\Forms\Container;
use Docalist\Forms\Theme;
use Docalist\Forms\EntryPicker;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ItemTest extends WP_UnitTestCase
{
    /**
     * Crée un item (mock).
     *
     * @return Item
     */
    protected function getItem()
    {
        return $this->getMockForAbstractClass(Item::class, func_get_args());
    }

    /**
     * Crée un containeur (mock).
     *
     * @return Container
     */
    protected function getContainer()
    {
        return new Container();
    }

    public function testConstruct()
    {
        $item = $this->getItem();
        $this->assertNull($item->getParent());

        $parent = $this->getContainer('dad');
        $item = $this->getItem($parent);
        $this->assertSame($parent, $item->getParent());
    }

    public function testGetType()
    {
        // On ne peut pas utiliser getElement() car ça retourne un mock avec un nom de classe
        // de la forme Mock_Element_xxx, ce qui fait que getType() ne retourne pas 'element'
        // Comme getType() est 'final', on peut tester avec n'importe quel type de champ.

        $this->assertSame('input', (new Input('a'))->getType());
        $this->assertSame('entrypicker', (new EntryPicker('a'))->getType());
    }

    public function testSetParent()
    {
        $item = $this->getItem();
        $group1 = $this->getContainer('group');
        $group2 = $this->getContainer('group2');

        $item->setParent($group1);
        $this->assertSame($group1, $item->getParent());

        $item->setParent($group2);
        $this->assertSame($group2, $item->getParent());

        $item->setParent(null);
        $this->assertNull($item->getParent());
    }

    public function testGetParentRootDepth()
    {
        $item = $this->getItem();
        $this->assertNull($item->getParent());
        $this->assertSame($item, $item->getRoot());
        $this->assertSame(0, $item->getDepth());

        $parent = $this->getContainer('dad');
        $parent->add($item);
        $this->assertSame($parent, $item->getParent());
        $this->assertSame($parent, $item->getRoot());
        $this->assertSame(1, $item->getDepth());

        $grandparent = $this->getContainer('grandpa');
        $grandparent->add($parent);
        $this->assertSame($parent, $item->getParent());
        $this->assertSame($grandparent, $item->getRoot());
        $this->assertSame(2, $item->getDepth());
    }

    public function testRender()
    {
        // On veut vérifier que Theme::render() est appellée quand on appelle Item::render()

        $item = $this->getItem();
        $theme = new ThemeMock();
        $this->assertSame('AbcXyz', $item->render($theme));
        $this->assertSame($theme->lastDisplay, [$item, null]);
    }
}
