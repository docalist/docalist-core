<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Type;

use WP_UnitTestCase;
use Docalist\Type\Entity;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
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
