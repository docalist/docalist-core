<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Forms;

use InvalidArgumentException;
use WP_UnitTestCase;
use Docalist\Forms\Select;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SelectTest extends WP_UnitTestCase
{
    public function testGetSetFirstOption()
    {
        $default = ['' => '…'];
        $select = new Select();


        $this->assertSame($default, $select->getFirstOption());

        $select->setFirstOption(false);
        $this->assertSame(false, $select->getFirstOption());

        $select->setFirstOption(true);
        $this->assertSame($default, $select->getFirstOption());

        $select->setFirstOption(false);
        $select->setFirstOption(true);
        $this->assertSame($default, $select->getFirstOption());

        $select->setFirstOption('choose');
        $this->assertSame(['' => 'choose'], $select->getFirstOption());

        $select->setFirstOption([-1 => 'na']);
        $this->assertSame([-1 => 'na'], $select->getFirstOption());
    }

    /**
     * Teste setFirstOption avec un tableau vide.
     */
    public function testSetFirstOptionEmptyArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('array must contain one item');

        (new Select())->setFirstOption([]);
    }

    /**
     * Teste setFirstOption avec un tableau de plus d'un élément.
     */
    public function testSetFirstOptionBadArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('array must contain one item');

        (new Select())->setFirstOption([1, 2]);
    }

    /**
     * Teste setFirstOption avec une mauvaise valeur.
     */
    public function testSetFirstOptionBadArg()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid firstOption');

        (new Select())->setFirstOption(new Select());
    }
}
