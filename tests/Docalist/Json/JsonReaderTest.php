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
namespace Docalist\Tests\Json;

use WP_UnitTestCase;
use Docalist\Json\JsonReader;

class JsonReaderTest extends WP_UnitTestCase
{
    /**
     * Crée un JsonReader pour le fichier json passé en paramètre.
     *
     * @param string $json
     *
     * @return JsonReader
     */
    public function fileReader($filename)
    {
        return new JsonReader($filename);
    }

    /**
     * Crée un JsonReader pour le source json passé en paramètre.
     *
     * @param string $json
     *
     * @return JsonReader
     */
    public function stringReader($json)
    {
        $uri = 'data:application/json;charset=UTF-8;base64,'.base64_encode($json);
        return new JsonReader($uri);
    }

    /**
     * Fournit des exemples de fichiers json considérés comme vides.
     *
     * @return string[][]
     */
    public function emptyJsonProvider()
    {
        return [
            [''],
            [' '],
            ["\t"],
            ["\n"],
            ["\r"],
            ["\t       \n\n\n\r\n\r      \t    \t    \n"],
        ];
    }

    /**
     * Teste des fichers json vides, avec ou sans espaces
     *
     * @dataProvider emptyJsonProvider
     */
    public function testEmpty($json)
    {
        $json = $this->stringReader($json);

        $this->assertTrue($json->isEof());
        $this->assertTrue($json->is(''));

        $this->assertFalse($json->is('{'));
        $this->assertFalse($json->isNull());
        $this->assertFalse($json->isBool());
        $this->assertFalse($json->isNumber());
        $this->assertFalse($json->isString());
        $this->assertFalse($json->isArray());
        $this->assertFalse($json->isObject());
    }

    /**
     * Vérifie qu'une exception est générée si on essaie de lire quelque chose dans un fichier vide.
     *
     * @dataProvider emptyJsonProvider
     *
     * @expectedException Docalist\Json\JsonParseException
     */
    public function testEmptyRead($json)
    {
        $this->stringReader($json)->readValue();
    }

    /**
     * Fournit des exemples de fichiers qui contiennent juste la valeur null.
     *
     * @return string[][]
     */
    public function nullProvider()
    {
        return [
            ['null'],
            [' null '],
            ["\tnull\t"],
            ["\nnull\n"],
        ];
    }

    /**
     * Teste des fichiers json qui contiennent uniquement la valeur null
     *
     * @dataProvider nullProvider
     */
    public function testNull($json)
    {
        $json = $this->stringReader($json);

        $this->assertFalse($json->isEof());
        $this->assertTrue($json->isNull());
        $this->assertTrue($json->is(null));

        $this->assertFalse($json->is('{'));
        $this->assertFalse($json->isBool());
        $this->assertFalse($json->isNumber());
        $this->assertFalse($json->isString());
        $this->assertFalse($json->isArray());
        $this->assertFalse($json->isObject());

        $this->assertNull($json->readNull());
        $this->assertTrue($json->isEof());
        $this->assertSame('', $json->readEof());
    }

    /**
     * Fournit des exemples de fichiers qui contiennent des booléens.
     *
     * @return array
     */
    public function boolProvider()
    {
        return [
            ['true', true],
            [" true\r", true],
            ["\ntrue\t", true],

            ['false', false],
            [" false\r", false],
            ["\nfalse\t", false],
        ];
    }

    /**
     * Teste des fichiers json qui contiennent des nombres.
     *
     * @dataProvider boolProvider
     */
    public function testBool($json, $expect)
    {
        $json = $this->stringReader($json);

        $this->assertFalse($json->isEof());
        $this->assertTrue($json->isBool());

        $this->assertFalse($json->is('{'));
        $this->assertFalse($json->isNull());
        $this->assertFalse($json->isNumber());
        $this->assertFalse($json->isString());
        $this->assertFalse($json->isArray());
        $this->assertFalse($json->isObject());

        $this->assertSame($expect, $json->readBool());
        $this->assertTrue($json->isEof());
        $this->assertSame('', $json->readEof());
    }

    /**
     * Fournit des exemples de fichiers qui contiennent des nombres.
     *
     * @return array
     */
    public function numberProvider()
    {
        return [
            ['0', 0],
            ['-0', 0],
            ['-1', -1],
            ['2', 2],
            ['22', 22],
            ['55.75466', 55.75466],
            ['-44.565', -44.565],
            ['55e-2', 55e-2],
            ['55E-2', 55E-2],
            ['69234.2423432E78', 69234.2423432E+78],
            ['69234.2423432e78', 69234.2423432E+78],
        ];
    }

    /**
     * Teste des fichiers json qui contiennent des nombres.
     *
     * @dataProvider numberProvider
     */
    public function testNumber($json, $expect)
    {
        $json = $this->stringReader($json);

        $this->assertFalse($json->isEof());
        $this->assertTrue($json->isNumber());

        $this->assertFalse($json->is('{'));
        $this->assertFalse($json->isNull());
        $this->assertFalse($json->isBool());
        $this->assertFalse($json->isString());
        $this->assertFalse($json->isArray());
        $this->assertFalse($json->isObject());

        $this->assertSame($expect, $json->readNumber());
        $this->assertTrue($json->isEof());
        $this->assertSame('', $json->readEof());
    }

    /**
     * Fournit des exemples de fichiers qui contiennent des chaines.
     *
     * @return array
     */
    public function stringProvider()
    {
        return [
            ['""', ''],
            ['"\""', '"'],
//             ['"\\\""', '\"'], //->FAILS
            ['"1"', '1'],
            ['"Hello world!"', 'Hello world!'],
            ['"\\Hello \"world\""', '\Hello "world"'],
            ['"\\\/ Hello \"world\" \\\/"', '\/ Hello "world" \/'],
            ['"\u0041\u0042\u0043\u0044\u0045"', 'ABCDE'],
        ];
    }

    /**
     * Teste des fichiers json qui contiennent des nombres.
     *
     * @dataProvider stringProvider
     */
    public function testString($json, $expect)
    {
        $json = $this->stringReader($json);

        $this->assertFalse($json->isEof());
        $this->assertTrue($json->isString());

        $this->assertFalse($json->is('{'));
        $this->assertFalse($json->isNull());
        $this->assertFalse($json->isBool());
        $this->assertFalse($json->isNumber());
        $this->assertFalse($json->isArray());
        $this->assertFalse($json->isObject());

        $this->assertSame($expect, $json->readString());
        $this->assertTrue($json->isEof());
        $this->assertSame('', $json->readEof());
    }

    /**
     * Fournit des exemples de fichiers qui contiennent des objets.
     *
     * @return array
     */
    public function objectProvider()
    {
        return [
            ["{}", (object) []],
            ["\t{\n}\r", (object) []],
            [' {"a":null,"b":true,"c":"C"}', (object) ['a'=>null,'b'=>true,'c'=>'C']],
            [' {"pi":3.14,"^":4,"c":{}}', (object) ['pi'=>3.14,'^'=>4,'c'=>(object)[] ]],
            [' {"in":{"the":{"mood":{}}}}', (object) ['in'=>(object)['the'=>(object)['mood'=>(object)[]]] ]],
        ];
    }

    /**
     * Teste des fichiers json qui contiennent des objets.
     *
     * @dataProvider objectProvider
     */
    public function testObject($json, $expect)
    {
        $json = $this->stringReader($json);

        $this->assertFalse($json->isEof());
        $this->assertTrue($json->isObject());
        $this->assertTrue($json->is('{'));

        $this->assertFalse($json->is('}'));
        $this->assertFalse($json->isNull());
        $this->assertFalse($json->isBool());
        $this->assertFalse($json->isString());
        $this->assertFalse($json->isNumber());
        $this->assertFalse($json->isArray());

        $this->assertEquals($expect, $json->readObject());
        $this->assertTrue($json->isEof());
        $this->assertSame('', $json->readEof());
    }
}
