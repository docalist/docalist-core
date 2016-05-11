<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
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
use Docalist\Tests\Type\Fixtures\Money;

class CompositeTest extends WP_UnitTestCase
{
    public function testNew()
    {
        $a = new Composite();
        $this->assertSame([], $a->getPhpValue());

        // valeurs par défaut (amount : 0, currency : EUR)
        $euro = new Money();
        $this->assertSame(['amount' => 0.0, 'currency' => 'EUR'], $euro->getPhpValue());

        // conversion entier -> float
        $euro = new Money(['amount' => 12]); // rem : la valeur par défaut ne s'applique pas dans ce cas
        $this->assertSame(['amount' => 12.], $euro->getPhpValue());

        // data() retourne les champs dans l'ordre du schéma (currency <-> amount)
        $euro = new Money(['currency' => 'USD', 'amount' => 12]);
        $this->assertSame(['amount' => 12., 'currency' => 'USD'], $euro->getPhpValue());
    }

    /**
     * @expectedException Docalist\Type\Exception\InvalidTypeException
     */
    public function testInvalidType()
    {
        (new Composite())->assign('true');
    }

    public function testSet()
    {
        $a = new Money();

        $a->amount = 12.0;
        $this->assertSame(['amount' => 12., 'currency' => 'EUR'], $a->getPhpValue());

        $a->amount = 16; // conversion int -> float
        $a->currency = '$'; // modifie champ déjà initialisé
        $this->assertSame(['amount' => 16., 'currency' => '$'], $a->getPhpValue());
    }

    public function testGet()
    {
        $a = new Money(['amount' => 16., 'currency' => '$']);

        $this->assertInstanceOf('Docalist\Type\Decimal', $a->amount);
        $this->assertInstanceOf('Docalist\Type\Text', $a->currency);

        $this->assertSame(16., $a->amount->getPhpValue());
        $this->assertSame('$', $a->currency->getPhpValue());
    }

    /** @expectedException InvalidArgumentException */
    public function testGetInexistant()
    {
        $a = new Money();
        $a->abcdef;
    }

    public function testIssetUnset()
    {
        $a = new Money();

        $this->assertTrue(isset($a->amount));
        $this->assertTrue(isset($a->currency));

        unset($a->amount);
        $this->assertFalse(isset($a->amount));
    }

    public function testCall()
    {
        $a = new Money(['amount' => 16., 'currency' => '$']);

        $this->assertSame(16., $a->amount());
        $this->assertSame('$', $a->currency());

        $a = new Money();

        $a->amount(17)->currency('€');

        $this->assertSame(17., $a->amount());
        $this->assertSame('€', $a->currency());
    }

    public function testToString()
    {
        $a = new Composite();
        $b = new Money();

        $this->assertSame('[]', $a->__toString());
        $this->assertStringMatchesFormat('{%w"amount": 0,%w"currency": "EUR"%w}', $b->__toString());
    }
}
