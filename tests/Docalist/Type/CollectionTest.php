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

namespace Docalist\Tests\Type;

use Docalist\Type\Exception\InvalidTypeException;
use InvalidArgumentException;
use WP_UnitTestCase;
use Docalist\Type\Any;
use Docalist\Type\Collection;
use Docalist\Tests\Type\Fixtures\Client;
use Docalist\Tests\DocalistTestCase;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class CollectionTest extends DocalistTestCase
{
    public function testNew(): void
    {
        $c = new Collection();
        $this->assertSame([], $c->getPhpValue());

        $c = new Collection(['a', true, 5, 3.14]);
        $this->assertSame(['a', true, 5, 3.14], $c->getPhpValue());
    }

    public function testInvalidType(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('expected array');

        new Collection('12');
    }

    public function testSet(): void
    {
        $c = new Collection();
        $c[12] = 12;
        $this->assertSame(12, $c[12]->getPhpValue());

        $c[] = 13;
        $this->assertSame(13, $c[13]->getPhpValue());

        $this->assertSame([12, 13], $c->getPhpValue());

        $c[] = new Any('aa');
        $this->assertSame([12, 13, 'aa'], $c->getPhpValue());
    }

    public function testIsset(): void
    {
        $c = new Collection();
        $this->assertFalse(isset($c[0]));

        $c = new Collection(['a']);
        $this->assertTrue(isset($c[0]));

        $c = new Collection();
        $c[412] = 'yep';
        $this->assertTrue(isset($c[412]));

        unset($c[412]);
        $this->assertFalse(isset($c[412]));

        $init = new Collection(['a']);
        $c = new Collection($init);
        $this->assertTrue($c == $init);
    }

    public function testGet(): void
    {
        $t = ['a', true, 5, 3.14];
        $c = new Collection($t);

        foreach ($t as $i => $v) {
            $this->assertInstanceOf(Any::class, $c[$i]);
            $this->assertSame($v, $c[$i]->getPhpValue());
        }
    }

    public function testGetInexistant(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Offset 0 does not exist');

        $a = new Collection();
        echo $a[0];
    }

    public function testCount(): void
    {
        $c = new Collection();
        $this->assertSame(0, $c->count());

        $c = new Collection(['a', true, 5, 3.14]);
        $this->assertSame(4, $c->count());

        unset($c[1]);
        $this->assertSame(3, $c->count());
    }

    public function testGetIterator(): void
    {
        $c = new Collection();
        $this->assertInstanceOf('Iterator', $c->getIterator());

        $gotit = false;
        foreach ($c->getIterator() as $i) {
            $gotit = true;
        }
        $this->assertFalse($gotit);

        $c = new Collection(['a', true, 5, 3.14]);
        $this->assertInstanceOf('Traversable', $c->getIterator());

        $t = [];
        foreach ($c->getIterator() as $i => $v) {
            $t[$i] = $v->getPhpValue();
        }
        $this->assertSame(['a', true, 5, 3.14], $t);
    }

    public function testFirst(): void
    {
        $c = new Collection();
        $this->assertNull($c->first());
        $this->assertNull($c->current());
        $this->assertNull($c->key());

        $c = new Collection(['a', true, 5, 3.14]);
        $this->assertInstanceOf(Any::class, $c->first());
        $this->assertSame('a', $c->first()->getPhpValue());
        $this->assertInstanceOf(Any::class, $c->current());
        $this->assertSame('a', $c->current()->getPhpValue());
        $this->assertSame(0, $c->key());
    }

    public function testLast(): void
    {
        $c = new Collection();
        $this->assertNull($c->last());
        $this->assertNull($c->current());
        $this->assertNull($c->key());

        $c = new Collection(['a', true, 5, 3.14]);
        $this->assertInstanceOf(Any::class, $c->last());
        $this->assertSame(3.14, $c->last()->getPhpValue());
        $this->assertInstanceOf(Any::class, $c->current());
        $this->assertSame(3.14, $c->current()->getPhpValue());
        $this->assertSame(3, $c->key());
    }

    public function testNext(): void
    {
        $c = new Collection();
        $this->assertNull($c->next());
        $this->assertNull($c->current());
        $this->assertNull($c->key());

        $c = new Collection(['a', true, 5, 3.14]);
        $c->first();
        $next = $c->next();
        $this->assertInstanceOf(Any::class, $next);
        $this->assertSame(true, $next->getPhpValue());
        $this->assertInstanceOf(Any::class, $c->current());
        $this->assertSame(true, $c->current()->getPhpValue());
        $this->assertSame(1, $c->key());
    }

    // key() et current() déjà testés avec first, last, next

    public function testKeys(): void
    {
        $c = new Collection();
        $this->assertSame([], $c->keys());

        $c = new Collection(['a', true, 5, 3.14]);
        $this->assertSame([0, 1, 2, 3], $c->keys());

        unset($c[1]);
        unset($c[3]);
        $this->assertSame([0, 2], $c->keys());

        $c[9] = 'neuf';
        $this->assertSame([0, 2, 9], $c->keys());

        $c->refreshKeys();
        $this->assertSame([0, 1, 2], $c->keys());
    }

    public function testKeyedCollection(): void
    {
        $client = new Client([
            'name' => 'Dupont',
            'factures' => [
                ['code' => 'f1', 'label' => 'facture 1', 'total' => 12],
                ['code' => 'f2', 'label' => 'facture 2', 'total' => 24],
                ['code' => 'f3', 'label' => 'facture 3', 'total' => 36],
            ],
        ]);

        $this->assertSame(['f1', 'f2', 'f3'], $client->factures->keys());

        $client->factures['willNotBeUsed'] = ['code' => 'f4', 'label' => 'facture 4', 'total' => 48];
        $this->assertSame(['f1', 'f2', 'f3', 'f4'], $client->factures->keys());

        $this->assertTrue(isset($client->factures['f1']));
        $this->assertFalse(isset($client->factures[0]));

        unset($client->factures['f2']);
        $this->assertFalse(isset($client->factures['f2']));
        $this->assertSame(['f1', 'f3', 'f4'], $client->factures->keys());

        //$client->factures['f4']->code = 'f999'; // la collection ne sait pas que le code change
        $client->factures['f4']->code->assign('f999'); // la collection ne sait pas que le code change
        $this->assertTrue(isset($client->factures['f4']));
        $this->assertFalse(isset($client->factures['f999']));
        $this->assertSame(['f1', 'f3', 'f4'], $client->factures->keys());

        $client->factures->refreshKeys(); // recrée les clés
        $this->assertFalse(isset($client->factures['f4']));
        $this->assertTrue(isset($client->factures['f999']));
        $this->assertSame(['f1', 'f3', 'f999'], $client->factures->keys());
    }

    public function testToString(): void
    {
        $a = new Collection();
        $b = new Collection(['a', 'b']);

        $this->assertSame('[]', $a->__toString());
        $this->assertStringMatchesFormat('[%w"a",%w"b"%w]', $b->__toString());
    }
}
