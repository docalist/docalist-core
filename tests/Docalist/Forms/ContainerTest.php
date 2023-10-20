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
use Docalist\Forms\Element;
use Docalist\Forms\Input;
use Docalist\Forms\Item;
use Docalist\Forms\Select;
use Docalist\Forms\Tag;
use Docalist\Forms\Textarea;
use Docalist\Tests\DocalistTestCase;
use InvalidArgumentException;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ContainerTest extends DocalistTestCase
{
    /**
     * Crée un container.
     */
    protected function getContainer(): Container
    {
        return new Container();
    }

    /**
     * Retourne un tableau contenant trois items.
     *
     * @return array<Item>
     */
    protected function getItems(): array
    {
        return [
            new Input('name'),
            new Select('country'),
            new Textarea('bio'),
            new Tag('p', 'content'),
        ];
    }

    public function testAdd(): void
    {
        $items = $this->getItems();
        $container = $this->getContainer();

        foreach ($items as $item) {
            $container->add($item);
        }

        $this->assertSame(count($items), $container->count());
        foreach ($items as $item) {
            $this->assertTrue($container->has($item));
            $this->assertSame($container, $item->getParent());
        }
    }

    /**
     * Teste les références circulaires.
     */
    public function testAddCircular1(): void
    {
        $container = $this->getContainer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Circular reference');

        $container->add($container);
    }

    /**
     * Teste les références circulaires.
     */
    public function testAddCircular2(): void
    {
        $container1 = $this->getContainer();
        $container2 = $this->getContainer();
        $container3 = $this->getContainer();

        $container1->add($container2);
        $container2->add($container3);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Circular reference');

        $container3->add($container1);
    }

    public function testAddItems(): void
    {
        $items = $this->getItems();
        $container = $this->getContainer();

        // Ajoute un item initial pour vérifier que addItems en fait pas un reset
        $initial = new Input();
        $container->add($initial);

        // Teste
        $container->addItems($items);
        $this->assertTrue($container->has($initial));
        $this->assertSame(1 + count($items), $container->count());
        foreach ($items as $item) {
            $this->assertTrue($container->has($item));
            $this->assertSame($container, $item->getParent());
        }
    }

    public function testRemove(): void
    {
        $items = $this->getItems();

        // Suppression par instance
        $container = $this->getContainer();
        $container->addItems($items);
        $this->assertSame(count($items), $container->count());

        $nb = count($items);
        foreach ($items as $item) {
            $container->remove($item);
            --$nb;
            $this->assertSame($nb, $container->count());
            $this->assertFalse($container->has($item));
            $this->assertNull($item->getParent());
        }

        // Suppression par nom d'élément
        $container->addItems($items);
        $this->assertSame(count($items), $container->count());

        $nb = count($items);
        foreach ($items as $item) {
            if ($item instanceof Element) {
                $container->remove($item->getName());
                --$nb;
                $this->assertSame($nb, $container->count());
                $this->assertFalse($container->has($item));
                $this->assertNull($item->getParent());
            }
        }
    }

    public function testRemoveAll(): void
    {
        $items = $this->getItems();
        $container = $this->getContainer();
        $container->addItems($items);
        $this->assertSame(count($items), $container->count());

        $container->removeAll();
        $this->assertSame(0, $container->count());

        foreach ($items as $item) {
            $this->assertFalse($container->has($item));
            $this->assertNull($item->getParent());
        }
    }

    public function testHasItems(): void
    {
        $container = $this->getContainer();
        $this->assertFalse($container->hasItems());

        $container->addItems($this->getItems());
        $this->assertTrue($container->hasItems());
    }

    public function testGetItems(): void
    {
        $items = $this->getItems();
        $container = $this->getContainer();
        $container->addItems($items);
        $this->assertSame($items, $container->getItems());
    }

    public function testSetItems(): void
    {
        $items = $this->getItems();
        $container = $this->getContainer();

        // Ajoute un item initial pour vérifier que setItems fait un reset
        $initial = new Input();
        $container->add($initial);

        // Teste
        $container->setItems($items);
        $this->assertFalse($container->has($initial));
        $this->assertSame(count($items), $container->count());
        foreach ($items as $item) {
            $this->assertTrue($container->has($item));
            $this->assertSame($container, $item->getParent());
        }
    }

    public function testCount(): void
    {
        $items = $this->getItems();
        $container = $this->getContainer()->setItems($items);
        $this->assertInstanceOf('Countable', $container);
        $this->assertSame(count($items), $container->count());
    }

    public function testIterator(): void
    {
        $items = $this->getItems();
        $container = $this->getContainer()->setItems($items);
        $this->assertInstanceOf('IteratorAggregate', $container);
        $this->assertInstanceOf('ArrayIterator', $container->getIterator());
        $this->assertSame($items, iterator_to_array($container->getIterator()));
    }

    public function testIsLabelable(): void
    {
        $container = $this->getContainer();
        $this->assertFalse($this->callNonPublic($container, 'isLabelable'));
    }

}
