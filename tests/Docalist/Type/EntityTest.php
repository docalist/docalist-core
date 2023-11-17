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

use LogicException;
use WP_UnitTestCase;
use Docalist\Type\Entity;
use Docalist\Tests\DocalistTestCase;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class EntityTest extends DocalistTestCase
{
    public function testAll(): void
    {
        $a = new Entity();
        $this->assertSame(null, $a->getID());

        $a->setID('abc12');
        $this->assertSame('abc12', $a->getID());

        $a = new Entity([], null, 'abc12');
        $this->assertSame('abc12', $a->getID());
    }

    // Désactivé, setId() ne génère plus d'exception
    // public function testIDAlreadyDefined(): void
    // {
    //     $this->expectException(LogicException::class);
    //     $this->expectExceptionMessage('expected float');

    //     $a = new Entity([], null, 'abc12');
    //     $a->setID('def13');
    // }
}
