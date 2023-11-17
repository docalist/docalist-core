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

use WP_UnitTestCase;
use Docalist\Type\Any;
use Docalist\Schema\Schema;
use Docalist\Tests\DocalistTestCase;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class AnyTest extends DocalistTestCase
{
    public function testValue(): void
    {
        $type = new Any();
        $this->assertNull($type->getPhpValue());

        $type = new Any(12);
        $this->assertSame(12, $type->getPhpValue());
    }

    public function testSchema(): void
    {
        $type = new Any();
        $this->assertSame($type->getSchema()->value(), Any::getDefaultSchema()->value());

        $schema = new Schema([]);
        $type = new Any(12, $schema);
        $this->assertSame($type->getSchema(), $schema);
    }

    public function testToString(): void
    {
        $a = new Any(12);
        $b = new Any([]);

        $this->assertSame('12', $a->__toString());
        $this->assertSame('[]', $b->__toString());
    }

    public function testSerialize(): void
    {
        $a = new Any([1, false, 0.12, 'fd']);

        $this->assertSame($a->serialize(), serialize([$a->getPhpValue(), $a->getSchema()]));
    }

    public function testUnserialize(): void
    {
        $a = new Any([1, false, 0.12, 'fd']);

        $b = unserialize(serialize($a));
        $this->assertTrue($a == $b);
    }

    public function testJsonSerialize(): void
    {
        $a = new Any([1, false, 0.12, 'fd']);

        $this->assertSame($a->getPhpValue(), $a->jsonSerialize());
    }
}
