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

use Docalist\Type\Object;
use Docalist\Schema\Schema;
use Docalist\Tests\Type\Fixtures\Money;
use Docalist\Type\Any;

class ObjectTest extends WP_UnitTestCase {
    public function testNew() {
        $a = new Object();
        $this->assertSame([], $a->value());

        // valeurs par défaut (amount : 0, currency : EUR)
        $euro = new Money();
        $this->assertSame(['amount' => 0.0, 'currency' => 'EUR'], $euro->value());

        // conversion entier -> float
        $euro = new Money(['amount' => 12]); // rem : la valeur par défaut ne s'applique pas dans ce cas
        $this->assertSame(['amount' => 12.], $euro->value());

        // data() retourne les champs dans l'ordre du schéma (currency <-> amount)
        $euro = new Money(['amount' => 12, 'conversion' => [ ['currency' => '$', 'amount' => 16.06] ] ]);
        $this->assertSame(['amount' => 12., 'conversion' => [ ['amount' => 16.06, 'currency' => '$'] ] ], $euro->value());
    }

    /** @expectedException Docalist\Type\Exception\InvalidTypeException */
    public function testInvalidType()
    {
        (new Object)->assign('true');
    }

    public function testSet() {
        $a = new Money();

        $a->amount = 12.0;
        $this->assertSame(['amount' => 12., 'currency' => 'EUR'], $a->value());

        $a->amount = 16; // conversion int -> float
        $a->currency = '$'; // modifie champ déjà initialisé
        $this->assertSame(['amount' => 16., 'currency' => '$'], $a->value());

        $a->timestamp = 123456; // initialise un champ qui ne l'est pas déjà
        $this->assertSame(['amount' => 16., 'currency' => '$', 'timestamp' => 123456], $a->value());

        $a->conversion = [ ['currency' => 'EUR', 'amount' => 12] ];
        $this->assertSame(['amount' => 16., 'currency' => '$', 'conversion' => [ ['amount' => 12., 'currency' => 'EUR'] ], 'timestamp' => 123456 ], $a->value());

        $a->conversion = null; // conversion réinitialisé à sa valeur par défaut ([]) puis filtré par value() donc supprimé
        $this->assertSame(['amount' => 16., 'currency' => '$', 'timestamp' => 123456], $a->value());

        $a->timestamp = null; // timestamp réinitialisé à sa valeur par défaut de la classe type (0) et conservé
        $this->assertSame(['amount' => 16., 'currency' => '$', 'timestamp' => 0], $a->value());

        $a->amount = null; // réinitialisé à la valeur par défaut qui figure dans le schéma (0)
        $a->currency = null; // réinitialisé à la valeur par défaut qui figure dans le schéma (EUR)
        $this->assertSame(['amount' => 0., 'currency' => 'EUR', 'timestamp' => 0], $a->value());
    }

    public function testGet() {
        $a = new Money(['amount' => 16., 'currency' => '$', 'conversion' => [ ['amount' => 12.] ], 'timestamp' => 123456 ]);

        $this->assertInstanceOf('Docalist\Type\Float', $a->amount);
        $this->assertInstanceOf('Docalist\Type\Text', $a->currency);
        $this->assertInstanceOf('Docalist\Type\Collection', $a->conversion);
        $this->assertInstanceOf('Docalist\Type\Integer', $a->timestamp);

        $this->assertSame(16., $a->amount->value());
        $this->assertSame('$', $a->currency->value());
        $this->assertSame([ ['amount' => 12.] ], $a->conversion->value());
        $this->assertSame(123456, $a->timestamp->value());

        $a = new Money();
        $this->assertSame(0, $a->timestamp());
    }

    /** @expectedException InvalidArgumentException */
    public function testGetInexistant()
    {
        $a = new Money();
        $a->abcdef;
    }

    public function testIssetUnset() {
        $a = new Money();

        $this->assertTrue(isset($a->amount));
        $this->assertTrue(isset($a->currency));
        $this->assertFalse(isset($a->conversion));
        $this->assertFalse(isset($a->timestamp));

        $a->timestamp = 123456;
        $this->assertTrue(isset($a->timestamp));
        unset($a->timestamp);
        $this->assertFalse(isset($a->timestamp));

        $a->conversion = [ ['amount' => 12.] ];
        $this->assertTrue(isset($a->conversion));
        unset($a->conversion);
        $this->assertFalse(isset($a->conversion));
    }

    public function testCall() {
        $a = new Money(['amount' => 16., 'currency' => '$', 'conversion' => [ ['amount' => 12.] ], 'timestamp' => 123456 ]);

//         $this->assertInstanceOf('Docalist\Type\Float', $a->amount());
//         $this->assertInstanceOf('Docalist\Type\Text', $a->currency());
//         $this->assertInstanceOf('Docalist\Type\Collection', $a->conversion());
//         $this->assertInstanceOf('Docalist\Type\Integer', $a->timestamp());

        $this->assertSame(16., $a->amount());
        $this->assertSame('$', $a->currency());
        $this->assertSame([ ['amount' => 12.] ], $a->conversion());
        $this->assertSame(123456, $a->timestamp());


        $a = new Money();

        $a->amount(16)->currency('$')->conversion([ ['amount' => 12.] ])->timestamp(123456);
//         $this->assertInstanceOf('Docalist\Type\Float', $a->amount());
//         $this->assertInstanceOf('Docalist\Type\Text', $a->currency());
//         $this->assertInstanceOf('Docalist\Type\Collection', $a->conversion());
//         $this->assertInstanceOf('Docalist\Type\Integer', $a->timestamp());

        $this->assertSame(16., $a->amount());
        $this->assertSame('$', $a->currency());
        $this->assertSame([ ['amount' => 12.] ], $a->conversion());
        $this->assertSame(123456, $a->timestamp());

        $a = new Money();
        $this->assertSame([], $a->conversion());

        $a = new Object();
        $this->assertSame(Any::classDefault(), $a->prop());
    }

    public function testToString() {
        $a = new Object();
        $b = new Money();

        $this->assertSame("{ }", $a->__toString());
        $this->assertStringMatchesFormat('{%wamount: 0%wcurrency: "EUR"%w}', $b->__toString());
    }
}