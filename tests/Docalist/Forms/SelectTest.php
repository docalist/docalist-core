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
namespace Docalist\Tests\Forms;

use WP_UnitTestCase;
use Docalist\Forms\Select;

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
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage array must contain one item
     */
    public function testSetFirstOptionEmptyArray()
    {
        (new Select())->setFirstOption([]);
    }

    /**
     * Teste setFirstOption avec un tableau de plus d'un élément.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage array must contain one item
     */
    public function testSetFirstOptionBadArray()
    {
        (new Select())->setFirstOption([1, 2]);
    }

    /**
     * Teste setFirstOption avec une mauvaise valeur.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage invalid firstOption
     */
    public function testSetFirstOptionBadArg()
    {
        (new Select())->setFirstOption(new Select());
    }
}
