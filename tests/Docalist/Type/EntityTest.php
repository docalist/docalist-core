<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
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
use Docalist\Type\Entity;

class EntityTest extends WP_UnitTestCase
{
    public function testAll()
    {
        $a = new Entity();
        $this->assertSame(null, $a->getID());

        $a->setID('abc12');
        $this->assertSame('abc12', $a->getID());

        $a = new Entity([], null, 'abc12');
        $this->assertSame('abc12', $a->getID());
    }

    /** @expectedException LogicException */
    public function testIDAlreadyDefined()
    {
        $a = new Entity([], null, 'abc12');
        $a->setID('def13');
    }
}
