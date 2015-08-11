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

use Docalist\Type\Any;
use Docalist\Schema\Schema, Docalist\Schema\Field;
use Docalist\Tests\Type\Fixtures\Money;

require_once __DIR__ . '/fixtures/GlobalType.php';
use GlobalType;

class AnyTest extends WP_UnitTestCase {
    public function testValue() {
        $type = new Any();
        $this->assertNull($type->value());

        $type = new Any(12);
        $this->assertSame(12, $type->value());
    }

    public function testReset() {
        $type = new Any();
        $this->assertSame($type, $type->reset(), 'Any::reset() retourne $this');

        $type = new Any(12);
        $type->reset();
        $this->assertNull($type->value(), 'Any::reset() assigne null');

        $type = new Any(12, new Field([ 'default' => 'xyz' ]));
        $type->reset();
        $this->assertSame('xyz', $type->value(), 'Any::reset() assigne la valeur par défaut du schéma');
    }

    public function testSchema() {
        $type = new Any();
        $this->assertNull($type->schema());

        $schema = new Schema([]);
        $type = new Any(12, $schema);
        $this->assertSame($type->schema(), $schema);
    }

    public function testEquals() {
        $a = new Any(12);
        $b = new Any(13);

        $this->assertTrue($a->equals($a));
        $this->assertTrue($b->equals($b));
        $this->assertFalse($a->equals($b));
        $this->assertFalse($b->equals($a));
    }

    public function testToString() {
        $a = new Any(12);
        $b = new Any([]);

        $this->assertSame('12', $a->__toString());
        $this->assertSame("[\n\n]", $b->__toString());
    }

    public function testSerialize() {
        $a = new Any([1, false, 0.12, 'fd']);

        $this->assertSame($a->serialize(), serialize($a->value()));
    }

    public function testUnserialize() {
        $a = new Any([1, false, 0.12, 'fd']);

        $b = unserialize(serialize($a));

        $this->assertTrue($a->equals($b));
    }

    public function testJsonSerialize() {
        $a = new Any([1, false, 0.12, 'fd']);

        $this->assertSame($a->value(), $a->jsonSerialize());
    }

    public function testClassName() {
        $this->assertSame('Docalist\Tests\Type\Fixtures\Money', Money::className());

        $this->assertSame('GlobalType', GlobalType::className());
    }

    public function testNs() {
        $this->assertSame('', GlobalType::ns());
    }
}