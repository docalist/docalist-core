<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Tests;

use WP_UnitTestCase;
use Docalist\Schema\Schema;
use Docalist\Tests\Type\Fixtures\Money;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SchemaTest extends WP_UnitTestCase
{
    public function testEmpty()
    {
        $schema = new Schema();
        $this->assertSame([], $schema->getProperties());

        $schema = new Schema([]);
        $this->assertSame([], $schema->getProperties());

        $schema = new Schema(null);
        $this->assertSame([], $schema->getProperties());
    }

    public function testTwoProperties()
    {
        $properties = ['label' => 'lbl', 'description' => 'desc'];
        $schema = new Schema($properties);

        $this->assertSame($properties, $schema->getProperties());
    }

    // Héritage simple depuis le type scalaire TableEntry
    public function testInheritTableEntry()
    {
        $properties = ['type' => 'Docalist\Type\TableEntry'];
        $schema = new Schema($properties);

        $result = [
            'type' => 'Docalist\Type\TableEntry',
            'label' => __('Entrée', 'docalist-core'),
            'description' => __('Choisissez dans la liste.', 'docalist-core'),
        ];

        $this->assertSame($result, $schema->getProperties());
    }

    // Héritage simple de TableEntry + surcharge d'une propriété + création d'une nouvelle propriété
    public function testInheritTableEntryAndOverride()
    {
        $schema = new Schema([
            'type' => 'Docalist\Type\TableEntry',
            'label' => 'label modifié',
            'newprop' => 'maprop',
        ]);

        $result = [
            'type' => 'Docalist\Type\TableEntry',
            'label' => 'label modifié',
            'description' => __('Choisissez dans la liste.', 'docalist-core'),
            'newprop' => 'maprop',
        ];

        $this->assertSame($result, $schema->getProperties());
    }

    // Héritage simple depuis le composite Money
    public function testInheritMoney()
    {
        $schema = new Schema(['type' => 'Docalist\Tests\Type\Fixtures\Money']);
        $parent = Money::getDefaultSchema();

        $this->assertSame($schema->type(), 'Docalist\Tests\Type\Fixtures\Money');
        $this->assertSame($schema->label(), $parent->label());
        $this->assertSame($schema->description(), $parent->description());

        $this->assertInstanceOf(Schema::class, $schema->getField('amount'));
        $this->assertInstanceOf(Schema::class, $schema->getField('currency'));

        $this->assertEquals($schema->getField('amount'), $parent->getField('amount'));
        $this->assertEquals($schema->getField('currency'), $parent->getField('currency'));
    }

    // Héritage du composite Money + surcharge propriétés + nouvelles propriétés
    public function testInheritMoneyAndOverride()
    {
        $schema = new Schema([
            'type' => 'Docalist\Tests\Type\Fixtures\Money',
            'label' => 'new label',
            'editor' => 'other-editor',
            'fields' => [
                'amount' => [
                    'label' => 'montant',
                    'zz' => 'ZZ',
                ],
            ],
        ]);

        $parent = Money::getDefaultSchema();

        $this->assertSame($schema->type(), 'Docalist\Tests\Type\Fixtures\Money');
        $this->assertSame($schema->label(), 'new label');
        $this->assertSame($schema->description(), $parent->description());
        $this->assertSame($schema->editor(), 'other-editor');

        $this->assertInstanceOf(Schema::class, $schema->getField('amount'));
        $this->assertInstanceOf(Schema::class, $schema->getField('currency'));

        $this->assertSame($schema->getField('amount')->label(), 'montant');
        $this->assertSame($schema->getField('amount')->zz(), 'ZZ');
        $this->assertSame($schema->getField('currency')->description(), $parent->getField('currency')->description());
        $this->assertSame($schema->getField('currency')->type(), 'Docalist\Type\Text');

        $this->assertEquals($schema->getField('currency'), $parent->getField('currency'));
    }

    public function testFieldsShortcuts()
    {
        $schema = new Schema(['fields' => ['code']]);
        $this->assertTrue($schema->hasField('code'));
        $this->assertSame('code', $schema->getField('code')->name());
        $this->assertNull($schema->getField('code')->type());

        $schema = new Schema(['fields' => ['message' => 'Docalist\Type\Text']]);
        $this->assertTrue($schema->hasField('message'));
        $this->assertSame('message', $schema->getField('message')->name());
        $this->assertSame('Docalist\Type\Text', $schema->getField('message')->type());
    }

    public function testCollection()
    {
        $schema = new Schema();
        $this->assertNull($schema->collection());
        $this->assertNull($schema->repeatable());

        $schema = new Schema(['fields' => ['code' => 'Docalist\Type\Text*']]);
        $this->assertSame('Docalist\Type\Text', $schema->getField('code')->type());
        $this->assertSame('Docalist\Type\Collection', $schema->getField('code')->collection());
        $this->assertNull($schema->getField('code')->repeatable());

        $schema = new Schema(['fields' => ['code' => ['type' => 'Docalist\Type\Text', 'collection' => 'Docalist\Type\Collection']]]);
        $this->assertSame('Docalist\Type\Text', $schema->getField('code')->type());
        $this->assertSame('Docalist\Type\Collection', $schema->getField('code')->collection());
        $this->assertNull($schema->getField('code')->repeatable());

        $schema = new Schema(['fields' => ['code' => 'Docalist\Tests\Type\Fixtures\TextCollection']]);
        $this->assertSame('Docalist\Type\Text', $schema->getField('code')->type());
        $this->assertSame('Docalist\Tests\Type\Fixtures\TextCollection', $schema->getField('code')->collection());
        $this->assertNull($schema->getField('code')->repeatable());

        $schema = new Schema(['fields' => ['code' => ['collection' => 'Docalist\Type\Collection']]]);
        $this->assertSame('Docalist\Type\Any', $schema->getField('code')->type());
        $this->assertSame('Docalist\Type\Collection', $schema->getField('code')->collection());
        $this->assertNull($schema->getField('code')->repeatable());
    }

    /**
     * Collection doit être un chaine.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage expected string
     */
    public function testCollectionTypeNotString()
    {
        new Schema(['fields' => [
            [
                'name' => 'test',
                'collection' => 12,
            ],
        ]]);
    }

    /**
     * Collection doit être une... collection.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage not a Collection
     */
    public function testBadCollection()
    {
        new Schema(['fields' => [
            [
                'name' => 'test',
                'collection' => 'Docalist\Type\Text',
            ],
        ]]);
    }

    /**
     * On ne peut pas utiliser à la fois 'type*' et 'collection'.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Collection defined twice
     */
    public function testCollectionDefinedTwice()
    {
        new Schema(['fields' => [
            [
                'name' => 'test',
                'type' => 'Docalist\Tests\Type\Fixtures\TextCollection',
                'collection' => 'Docalist\Tests\Type\Fixtures\TextCollection',
            ],
        ]]);
    }

    public function testHasFields()
    {
        $schema = new Schema();
        $this->assertFalse($schema->hasFields());

        $schema = new Schema(['fields' => ['a', 'b']]);
        $this->assertTrue($schema->hasFields());

        $schema = new Schema(['type' => 'Docalist\Tests\Type\Fixtures\Money']);
        $this->assertTrue($schema->hasFields());
    }

    public function testGetFields()
    {
        $schema = new Schema();
        $this->assertSame([], $schema->getFields());

        $schema = new Schema(['fields' => ['a', 'b']]);
        $fields = $schema->getFields();
        $this->assertSame('array', gettype($fields));
        $this->assertSame(2, count($fields));

        $this->assertTrue(isset($fields['a']));
        $this->assertInstanceOf(Schema::class, $fields['a']);

        $this->assertTrue(isset($fields['b']));
        $this->assertInstanceOf(Schema::class, $fields['b']);
    }

    public function testGetFieldNames()
    {
        $schema = new Schema();
        $this->assertSame([], $schema->getFieldNames());

        $schema = new Schema(['fields' => ['a', 'b']]);
        $this->assertSame('array', gettype($schema->getFieldNames()));
        $this->assertSame(['a', 'b'], $schema->getFieldNames());
    }

    public function testGetField()
    {
        $schema = new Schema(['fields' => ['a', 'b']]);
        $this->assertInstanceOf(Schema::class, $schema->getField('a'));
        $this->assertInstanceOf(Schema::class, $schema->getField('b'));
    }

    /**
     * Appel de getField avec un champ qui n'existe pas.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage does not exist
     */
    public function testGetFieldInexistant()
    {
        $schema = new Schema();
        $schema->getField('a');
    }

    public function testCall()
    {
        $schema = new Schema();
        $this->assertNull($schema->label());
        $this->assertNull($schema->description());

        $schema = new Schema([
            'label' => 'lbl',
            'fields' => [
                'a' => ['label' => 'A'],
                'b',
            ],
        ]);

        $this->assertSame('lbl', $schema->label());
        $this->assertNull($schema->description());

        $this->assertTrue(is_array($schema->fields()));
        $this->assertSame(2, count($schema->fields()));

        $this->assertSame('a', $schema->getField('a')->name());
        $this->assertSame('A', $schema->getField('a')->label());
        $this->assertNull($schema->getField('a')->description());

        $this->assertSame('b', $schema->getField('b')->name());
        $this->assertNull($schema->getField('b')->label());
        $this->assertNull($schema->getField('b')->description());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage _call() called with arguments
     */
    public function testCallWithArguments()
    {
        $schema = new Schema();
        $schema->label('new label');
    }

    /**
     * Un scalaire ne peut pas avoir de champs.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Scalar type can not have fields
     */
    public function testScalarWithFields()
    {
        new Schema(['type' => 'Docalist\Type\Text', 'fields' => []]);
    }

    /**
     * La propriété 'fields' doit être un tableau.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage 'fields' must be an array
     */
    public function testBadFields()
    {
        new Schema(['fields' => true]);
    }

    /**
     * Le type d'un champ doit être un type docalist ou un schéma.
     */
    public function testType()
    {
        $schema = new Schema(['type' => 'Docalist\Type\Any']);
        $this->assertSame('Docalist\Type\Any', $schema->type());

        $schema = new Schema(['type' => 'Docalist\Schema\Schema']);
        $this->assertSame('Docalist\Schema\Schema', $schema->type());
    }

    /**
     * Le type d'un champ doit être un type docalist ou un schéma.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid type
     */
    public function testBadType()
    {
        new Schema(['type' => 'stdClass']);
    }

    /**
     * Le type d'un champ doit être compatible avec le type de la collection.
     */
    public function testCollectionType()
    {
        new Schema([
            'type' => 'Docalist\Type\Text',
            'collection' => 'Docalist\Tests\Type\Fixtures\TextCollection',
        ]);

        new Schema([
            'type' => 'Docalist\Type\LargeText',
            'collection' => 'Docalist\Tests\Type\Fixtures\TextCollection',
        ]);
    }

    /**
     * Le type d'un champ doit être compatible avec le type de la collection.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage is not compatible with collection type
     */
    public function testBadCollectionType()
    {
        new Schema([
            'type' => 'Docalist\Type\Any',
            'collection' => 'Docalist\Tests\Type\Fixtures\TextCollection',
        ]);
    }

    /**
     * Propriétés d'un champ : soit une chaine (type), soit un tableau.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid properties for field
     */
    public function testInvalidField()
    {
        new Schema(['fields' => [
            'code' => true,
        ]]);
    }

    /**
     * Les noms de champ doivent être uniques.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage defined twice
     */
    public function testDuplicateField()
    {
        new Schema(['fields' => [
            'code' => ['label' => 'un'],
            ['name' => 'code'],
        ]]);
    }

    /**
     * Un champ doit avoir un nom.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Field without name
     */
    public function testFieldWithoutName()
    {
        new Schema(['fields' => [
            ['type' => 'Docalist\Type\Text'],
        ]]);
    }

    /**
     * Type doit être un chaine.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage expected string
     */
    public function testFieldTypeNotString()
    {
        new Schema(['fields' => [
            ['type' => 12],
        ]]);
    }

    public function testGetDefaultValue()
    {
        $schema = new Schema();
        $this->assertNull($schema->getDefaultValue());

        $schema = new Schema([
            'type' => 'Docalist\Type\Text',
            'default' => 'aa',
        ]);
        $this->assertSame('aa', $schema->getDefaultValue());

        $schema = new Schema([
            'type' => 'Docalist\Type\Text*',
            'default' => ['aa', 'bb'],
        ]);
        $this->assertSame(['aa', 'bb'], $schema->getDefaultValue());

        $schema = new Schema([
            'type' => 'Docalist\Type\Composite',
            'fields' => ['a', 'b'],
            'default' => ['a' => 'A', 'b' => 'B'],
        ]);
        $this->assertSame(['a' => 'A', 'b' => 'B'], $schema->getDefaultValue());

        $schema = new Schema([
            'type' => 'Docalist\Type\Composite',
            'fields' => [
                'a' => [
                    'default' => 'x',
                ],
                'b' => [
                    'default' => 'y',
                ],
            ],
        ]);
        $this->assertSame(['a' => 'x', 'b' => 'y'], $schema->getDefaultValue());

        $schema = new Schema([
            'type' => 'Docalist\Type\Composite',
            'fields' => [
                'a' => [
                ],
                'b' => [
                    'default' => 'y',
                ],
            ],
        ]);
        $this->assertSame(['b' => 'y'], $schema->getDefaultValue());

        $schema = new Schema([
            'type' => 'Docalist\Type\Composite',
            'fields' => [
                'a' => [
                    'type' => 'Docalist\Type\Composite',
                    'fields' => [
                        'a1',
                        'a2' => [
                            'default' => 'A2',
                        ],
                    ],
                ],
                'b' => [
                    'default' => 'B',
                ],
            ],
        ]);
        $this->assertSame(['a' => ['a2' => 'A2'], 'b' => 'B'], $schema->getDefaultValue());

        $schema = new Schema([ // idem mais collection
            'type' => 'Docalist\Type\Composite*',
            'fields' => [
                'a' => [
                    'type' => 'Docalist\Type\Composite',
                    'fields' => [
                        'a1',
                        'a2' => [
                            'default' => 'A2',
                        ],
                    ],
                ],
                'b' => [
                    'default' => 'B',
                ],
            ],
        ]);
        $this->assertSame([['a' => ['a2' => 'A2'], 'b' => 'B']], $schema->getDefaultValue());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Field name defined twice
     */
    public function testNameDefinedTwice()
    {
        new Schema([
            'type' => 'Docalist\Type\Composite',
            'fields' => [
                'a' => [
                    'name' => 'b',
                ],
            ],
        ]);
    }

    public function testJsonSerialize()
    {
        $schema = new Schema([
            'type' => 'Docalist\Type\Composite',
            'fields' => ['a', 'b'],
        ]);

        $this->assertSame($schema->getProperties(), $schema->jsonSerialize());
    }
}
