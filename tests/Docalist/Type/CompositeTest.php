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
use Docalist\Type\Composite;
use Docalist\Tests\Type\Fixtures\Money;
use Docalist\Type\Decimal;
use Docalist\Type\Text;
use Docalist\Tests\DocalistTestCase;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class CompositeTest extends DocalistTestCase
{
    private function newComposite(): Composite
    {
        return new class() extends Composite {};
    }

    public function testNew(): void
    {
        $a = $this->newComposite();
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

    public function testInvalidType(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('expected array');

        $this->newComposite()->assign('true');
    }

    public function testSet(): void
    {
        $a = new Money();

        $a->amount->assign(12.0);
        $this->assertSame(['amount' => 12., 'currency' => 'EUR'], $a->getPhpValue());

        $a->amount->assign(16); // conversion int -> float
        $a->currency->assign('$'); // modifie champ déjà initialisé
        $this->assertSame(['amount' => 16., 'currency' => '$'], $a->getPhpValue());
    }

    public function testGet(): void
    {
        $a = new Money(['amount' => 16., 'currency' => '$']);

        $this->assertInstanceOf(Decimal::class, $a->amount);
        $this->assertInstanceOf(Text::class, $a->currency);

        $this->assertSame(16., $a->amount->getPhpValue());
        $this->assertSame('$', $a->currency->getPhpValue());
    }

    public function testGetInexistant(): void
    {
        $a = new Money();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "abcdef" does not exist');

        // @phpstan-ignore-next-line
        $a->abcdef;
    }

    public function testIssetUnset(): void
    {
        $a = new Money();

        $this->assertTrue(isset($a->amount));
        $this->assertTrue(isset($a->currency));

        unset($a->amount);
        $this->assertFalse(isset($a->amount));
    }

    public function testCall(): void
    {
        $a = new Money(['amount' => 16., 'currency' => '$']);

        $this->assertSame(16., $a->amount());
        $this->assertSame('$', $a->currency());

        $a = new Money();

        $return = $a->amount(17);
        $this->assertSame($a, $return);
        $this->assertSame(17., $a->amount());

        $return = $a->currency('€');
        $this->assertSame($a, $return);
        $this->assertSame('€', $a->currency());
    }

    public function testToString(): void
    {
        $a = $this->newComposite();
        $b = new Money();

        $this->assertSame('[]', $a->__toString());
        $this->assertStringMatchesFormat('{%w"amount": 0,%w"currency": "EUR"%w}', $b->__toString());
    }
}
