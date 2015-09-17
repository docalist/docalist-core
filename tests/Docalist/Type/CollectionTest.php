<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */

namespace Docalist\Tests\Type;

use WP_UnitTestCase;

use Docalist\Type\Composite;
use Docalist\Type\Collection;

use Docalist\Tests\Type\Fixtures\Client;
use Docalist\Tests\Type\Fixtures\Facture;
use Docalist\Type\Any;

class CollectionTest extends WP_UnitTestCase {
    public function testNew() {
        $c = new Collection();
        $this->assertSame([], $c->value());

        $c = new Collection(['a', true, 5, 3.14]);
        $this->assertSame(['a', true, 5, 3.14], $c->value());
    }

    /** @expectedException Docalist\Type\Exception\InvalidTypeException */
    public function testInvalidType()
    {
        new Collection('12');
    }

    public function testSet() {
        $c = new Collection();
        $c[12] = 12;
        $this->assertSame(12, $c[12]->value());

        $c[] = 13;
        $this->assertSame(13, $c[13]->value());

        $this->assertSame([12,13], $c->value());

        $c[] = new Any('aa');
        $this->assertSame([12,13,'aa'], $c->value());
    }

    public function testIsset() {
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
        $this->assertTrue($c->equals($init));
    }

    public function testGet() {
        $t = ['a', true, 5, 3.14];
        $c = new Collection($t);

        foreach($t as $i => $v) {
            $this->assertInstanceOf('Docalist\Type\Any', $c[$i]);
            $this->assertSame($t[$i], $c[$i]->value());
        }
    }

    /** @expectedException InvalidArgumentException */
    public function testGetInexistant()
    {
        $a = new Collection();
        echo $a[0];
    }

    public function testCount() {
        $c = new Collection();
        $this->assertSame(0, $c->count());

        $c = new Collection(['a', true, 5, 3.14]);
        $this->assertSame(4, $c->count());

        unset($c[1]);
        $this->assertSame(3, $c->count());
    }

    public function testGetIterator() {
        $c = new Collection();
        $this->assertInstanceOf('Iterator', $c->getIterator());

        $gotit = false;
        foreach($c->getIterator() as $i) {
            $gotit = true;
        }
        $this->assertFalse($gotit);

        $c = new Collection(['a', true, 5, 3.14]);
        $this->assertInstanceOf('Iterator', $c->getIterator());

        $t = [];
        foreach($c->getIterator() as $i => $v) {
            $t[$i] = $v->value();
        }
        $this->assertSame(['a', true, 5, 3.14], $t);
    }

    public function testFirst() {
        $c = new Collection();
        $this->assertFalse($c->first());
        $this->assertFalse($c->current());
        $this->assertNull($c->key());

        $c = new Collection(['a', true, 5, 3.14]);
        $this->assertInstanceOf('Docalist\Type\Any', $c->first());
        $this->assertSame('a', $c->first()->value());
        $this->assertInstanceOf('Docalist\Type\Any', $c->current());
        $this->assertSame('a', $c->current()->value());
        $this->assertSame(0, $c->key());
    }

    public function testLast() {
        $c = new Collection();
        $this->assertFalse($c->last());
        $this->assertFalse($c->current());
        $this->assertNull($c->key());

        $c = new Collection(['a', true, 5, 3.14]);
        $this->assertInstanceOf('Docalist\Type\Any', $c->last());
        $this->assertSame(3.14, $c->last()->value());
        $this->assertInstanceOf('Docalist\Type\Any', $c->current());
        $this->assertSame(3.14, $c->current()->value());
        $this->assertSame(3, $c->key());
    }

    public function testNext() {
        $c = new Collection();
        $this->assertFalse($c->next());
        $this->assertFalse($c->current());
        $this->assertNull($c->key());

        $c = new Collection(['a', true, 5, 3.14]);
        $c->first();
        $next = $c->next();
        $this->assertInstanceOf('Docalist\Type\Any', $next);
        $this->assertSame(true, $next->value());
        $this->assertInstanceOf('Docalist\Type\Any', $c->current());
        $this->assertSame(true, $c->current()->value());
        $this->assertSame(1, $c->key());
    }

    // key() et current() déjà testés avec first, last, next

    public function testKeys() {
        $c = new Collection();
        $this->assertSame([], $c->keys());

        $c = new Collection(['a', true, 5, 3.14]);
        $this->assertSame([0,1,2,3], $c->keys());

        unset($c[1]);
        unset($c[3]);
        $this->assertSame([0,2], $c->keys());

        $c[9] = 'neuf';
        $this->assertSame([0,2,9], $c->keys());

        $c->refreshKeys();
        $this->assertSame([0,1,2], $c->keys());
    }

    public function testKeyedCollection() {
        $client = new Client([
            'name' => 'Dupont',
            'factures' => [
                [ 'code' => 'f1', 'label' => 'facture 1', 'total' => 12],
                [ 'code' => 'f2', 'label' => 'facture 2', 'total' => 24],
                [ 'code' => 'f3', 'label' => 'facture 3', 'total' => 36],
            ]
        ]);

        $this->assertSame(['f1', 'f2', 'f3'], $client->factures->keys());

        $client->factures['willNotBeUsed'] = [ 'code' => 'f4', 'label' => 'facture 4', 'total' => 48];
        $this->assertSame(['f1', 'f2', 'f3', 'f4'], $client->factures->keys());

        $this->assertTrue(isset($client->factures['f1']));
        $this->assertFalse(isset($client->factures[0]));

        unset($client->factures['f2']);
        $this->assertFalse(isset($client->factures['f2']));
        $this->assertSame(['f1', 'f3', 'f4'], $client->factures->keys());

        $client->factures['f4']->code = 'f999'; // la collection ne sait pas que le code change
        $this->assertTrue(isset($client->factures['f4']));
        $this->assertFalse(isset($client->factures['f999']));
        $this->assertSame(['f1', 'f3', 'f4'], $client->factures->keys());

        $client->factures->refreshKeys(); // recrée les clés
        $this->assertFalse(isset($client->factures['f4']));
        $this->assertTrue(isset($client->factures['f999']));
        $this->assertSame(['f1', 'f3', 'f999'], $client->factures->keys());
    }

    public function testToString() {
        $a = new Collection();
        $b = new Collection(['a', 'b']);

        $this->assertSame("[ ]", $a->__toString());
        $this->assertStringMatchesFormat('[%w"a"%w"b"%w]', $b->__toString());
    }
}