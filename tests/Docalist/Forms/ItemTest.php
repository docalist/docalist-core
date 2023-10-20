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
use Docalist\Forms\EntryPicker;
use Docalist\Forms\Input;
use Docalist\Forms\Item;
use Docalist\Tests\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ItemTest extends DocalistTestCase
{
    /**
     * Crée un item.
     */
    protected function getItem(Container $parent = null): Item
    {
        return new class($parent) extends Item {};
    }

    /**
     * Crée un containeur .
     */
    protected function getContainer(string $name = ''): Container
    {
        return new Container($name);
    }

    public function testConstruct(): void
    {
        $item = $this->getItem();
        $this->assertNull($item->getParent());

        $parent = $this->getContainer('dad');
        $item = $this->getItem($parent);
        $this->assertSame($parent, $item->getParent());
    }

    public function testHasLayout(): void
    {
        $this->assertFalse($this->callNonPublic($this->getItem(), 'hasLayout'));
    }

    public function testHasLabelBlock(): void
    {
        $this->assertFalse($this->callNonPublic($this->getItem(), 'hasLabelBlock'));
    }

    public function testIsLabelable(): void
    {
        $this->assertTrue($this->callNonPublic($this->getItem(), 'isLabelable'));
    }

    public function testHasDescriptionBlock(): void
    {
        $this->assertFalse($this->callNonPublic($this->getItem(), 'hasDescriptionBlock'));
    }

    public function testGetType(): void
    {
        // On ne peut pas utiliser getItem()->getType() car ça retourne le nom de la classe anonyme
        // Comme getType() est 'final', on peut tester avec n'importe quel type de champ.

        $this->assertSame('input', (new Input('a'))->getType());
        $this->assertSame('entrypicker', (new EntryPicker('a'))->getType());
    }

    public function testSetParent(): void
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

    public function testGetParentRootDepth(): void
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

    public function testRender(): void
    {
        // On veut vérifier que Theme::render() est appellée quand on appelle Item::render()

        $item = $this->getItem();
        $theme = new ThemeMock();
        $this->assertSame('AbcXyz', $item->render($theme));
        $this->assertSame($theme->lastDisplay, [$item, null]);
    }

    public function testDisplay(): void
    {
        // On veut vérifier que Theme::render() est appellée quand on appelle Item::render()

        $item = $this->getItem();
        $theme = new ThemeMock();
        ob_start();
        $item->display($theme);
        $output = ob_get_clean();
        $this->assertSame('AbcXyz', $output);
        $this->assertSame($theme->lastDisplay, [$item]);
    }

    public function testGetPath(): void
    {
        $item = $this->getItem();
        $this->assertSame('', $item->getPath());

        $group1 = $this->getContainer('group1');
        $group1->add($item);
        $this->assertSame('group1', $item->getPath());

        $group2 = $this->getContainer('group2');
        $group2->add($group1);
        $this->assertSame('group2/group1', $item->getPath());

        $this->assertSame('group2»group1', $item->getPath('»'));
    }
}
