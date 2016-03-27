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
use Docalist\Type\Any;
use Docalist\Schema\Schema;

class AnyTest extends WP_UnitTestCase
{
    public function testValue()
    {
        $type = new Any();
        $this->assertNull($type->getPhpValue());

        $type = new Any(12);
        $this->assertSame(12, $type->getPhpValue());
    }

    public function testSchema()
    {
        $type = new Any();
        $this->assertSame($type->schema()->value(), Any::getDefaultSchema()->value());

        $schema = new Schema([]);
        $type = new Any(12, $schema);
        $this->assertSame($type->schema(), $schema);
    }

    public function testToString()
    {
        $a = new Any(12);
        $b = new Any([]);

        $this->assertSame('12', $a->__toString());
        $this->assertSame('[]', $b->__toString());
    }

    public function testSerialize()
    {
        $a = new Any([1, false, 0.12, 'fd']);

        $this->assertSame($a->serialize(), serialize([$a->getPhpValue(), $a->schema()]));
    }

    public function testUnserialize()
    {
        $a = new Any([1, false, 0.12, 'fd']);

        $b = unserialize(serialize($a));
        $this->assertTrue($a == $b);
    }

    public function testJsonSerialize()
    {
        $a = new Any([1, false, 0.12, 'fd']);

        $this->assertSame($a->getPhpValue(), $a->jsonSerialize());
    }
}
