<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Json;

use WP_UnitTestCase;
use Docalist\Json\JsonReader;
use Docalist\Json\JsonParseException;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
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
            'empty' => [''],
            'one space' => [' '],
            'one tab' => ["\t"],
            'one \n' => ["\n"],
            'one \r' => ["\r"],
            'multiple spaces, tabs and newlines' => ["\t       \n\n\n\r\n\r      \t    \t    \n"],
        ];
    }

    /**
     * Vérifie que les méthodes isXX() retournent false quand on leur passe un fichier vide (sauf isEof)
     *
     * @dataProvider emptyJsonProvider
     */
    public function testIsOnEmptyFile($json)
    {
        $json = $this->stringReader($json);

        $this->assertFalse($json->is('{'));
        $this->assertFalse($json->isArray());
        $this->assertFalse($json->isBool());
        $this->assertTrue($json->isEof());
        $this->assertFalse($json->isNull());
        $this->assertFalse($json->isNumber());
        $this->assertFalse($json->isObject());
        $this->assertFalse($json->isString());
        $this->assertFalse($json->isValue());

        $this->assertTrue($json->is(''));
    }

    /**
     * Vérifie les méthodes getXX() génèrent une exception quand on leur passe un fichier vide (sauf getEof)
     *
     * @dataProvider emptyJsonProvider
     */
    public function testGetOnEmptyFile($json)
    {
        $json = $this->stringReader($json);

        $methods = ['get', 'getArray', 'getBool', 'getNull', 'getNumber', 'getObject', 'getString', 'getValue'];

        foreach ($methods as $method) {
            try {
                $method === 'get' ? $json->get('{') : $json->$method();
            } catch (JsonParseException $e) {
                continue; // ok, on a eu une exception
            }

            // La méthode n'a pas généré d'exception, test fails
            $this->fail("method $method should throw an exception when called on empty file");
        }
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

        // Initiallement
        $this->assertFalse($json->is('{'));
        $this->assertFalse($json->isArray());
        $this->assertFalse($json->isBool());
        $this->assertFalse($json->isEof());
        $this->assertTrue($json->isNull());
        $this->assertFalse($json->isNumber());
        $this->assertFalse($json->isObject());
        $this->assertFalse($json->isString());
        $this->assertTrue($json->isValue());

        // Lit la valeur null
        $this->assertNull($json->getNull());

        // Eof
        $this->assertTrue($json->isEof());
        $this->assertSame('', $json->getEof());
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

        // Initiallement
        $this->assertFalse($json->is('{'));
        $this->assertFalse($json->isArray());
        $this->assertTrue($json->isBool());
        $this->assertFalse($json->isEof());
        $this->assertFalse($json->isNull());
        $this->assertFalse($json->isNumber());
        $this->assertFalse($json->isObject());
        $this->assertFalse($json->isString());
        $this->assertTrue($json->isValue());

        // Lit le booléen
        $this->assertTrue(is_bool($json->getBool()));

        // Eof
        $this->assertTrue($json->isEof());
        $this->assertSame('', $json->getEof());
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

        // Initiallement
        $this->assertFalse($json->is('{'));
        $this->assertFalse($json->isArray());
        $this->assertFalse($json->isBool());
        $this->assertFalse($json->isEof());
        $this->assertFalse($json->isNull());
        $this->assertTrue($json->isNumber());
        $this->assertFalse($json->isObject());
        $this->assertFalse($json->isString());
        $this->assertTrue($json->isValue());

        // Lit le nombre
        $this->assertSame($expect, $json->getNumber());

        // Eof
        $this->assertTrue($json->isEof());
        $this->assertSame('', $json->getEof());
    }

    /**
     * Fournit des exemples de fichiers qui contiennent des chaines JSON valides.
     *
     * @return array
     */
    public function stringProvider()
    {
        $maxString = str_repeat('x', JsonReader::STRING_MAX_LEN - 2);
        $limit0 = str_repeat('x', JsonReader::CHUNK_SIZE);
        $limit1 = str_repeat('x', JsonReader::CHUNK_SIZE - 1);
        $limit2 = str_repeat('x', JsonReader::CHUNK_SIZE - 2);

        return [
            ['""', ''], // Chaine vide : input='' -> json="" -> php='""'

            ['"Hello"', 'Hello'], // Chaine simple : input='Hello' -> json="Hello" -> php='"Hello"'

            ['"\\""', '"'], // Guillemet double : input='"' -> json="\"" -> php='"\\""'
            ['"\\\\\\""', '\\"'], // Antislash + guillemet double : input='\\"' -> json="\\\"" -> php='"\\\\\\""'

            ['"1"', '1'],  // Un chiffre : input='1' -> json="1" -> php='"1"'
            ['"a\\"b\\"c"', 'a"b"c'], // Guillemets inclus : input='a"b"c' -> json="a\"b\"c" -> php='"a\\"b\\"c"'
            ['"ab\\"c\\""', 'ab"c"'], // Guillemets de fin : input='ab"c"' -> json="ab\"c\"" -> php='"ab\\"c\\""'
            ['"\\\\"', '\\'], // antislash seul : input='\\' -> json="\\" -> php='"\\\\"'
            ['"\\\\a"', '\\a'], // antislash début : input='\\a' -> json="\\a" -> php='"\\\\a"'
            ['"a\\\\"', 'a\\'], // Antislash de fin : input='a\\' -> json="a\\" -> php='"a\\\\"'
            ['"a\\\\b"', 'a\\b'], // Antislash milieu : input='a\\b' -> json="a\\b" -> php='"a\\\\b"'

            ['"\\/"', '/'], // slash échappé : input='/' -> json="\/" -> php='"\\/"'
            ['"/"', '/'], // slash non échappé : input='/' -> json="/" -> php='"/"'
            ['"\\\\\\/"', '\\/'], // Antislash + slash échappé : input='\\/' -> json="\\\/" -> php='"\\\\\\/"'
            ['"\\\\/"', '\\/'], // Antislash + slash non échappé : input='\\/' -> json="\\/" -> php='"\\\\/"'

            ['"\\u00c4\\u0042\\u0043\\u0044\\u00Ca"', 'ÄBCDÊ'], // Séquences unicode valides
            ['"\uD834\uDD1E"', html_entity_decode('&#x1d11e;')], // Une clé de SOL (http://www.fileformat.info/info/unicode/char/1D11E/index.htm)

            ['"' . $maxString . '"', $maxString], // La plus grande chaine qu'on peut avoir
            ['"' . $limit0. '"', $limit0], // Guillemet juste en limite de chunk
            ['"' . $limit1. '"', $limit1], // Guillemet juste en limite de chunk
            ['"' . $limit2. '"', $limit2], // Guillemet juste en limite de chunk
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

        // Initiallement
        $this->assertFalse($json->is('{'));
        $this->assertFalse($json->isArray());
        $this->assertFalse($json->isBool());
        $this->assertFalse($json->isEof());
        $this->assertFalse($json->isNull());
        $this->assertFalse($json->isNumber());
        $this->assertFalse($json->isObject());
        $this->assertTrue($json->isString());
        $this->assertTrue($json->isValue());

        // Lit la chaine
        $this->assertSame($expect, $json->getString());

        // Eof
        $this->assertTrue($json->isEof());
        $this->assertSame('', $json->getEof());
    }

    /**
     * Vérifie qu'une exception est générée si on a une chaine plus longue que STRING_MAX_LEN
     */
    public function testStringTooLong()
    {
        $json = $this->stringReader('"' . str_repeat('x', JsonReader::STRING_MAX_LEN), '"'); // ie max + 2 en tout

        $this->expectException(JsonParseException::class);
        $this->expectExceptionMessage('string exceeds');

        $json->getString();
    }

    /**
     * Vérifie qu'une exception est générée si on a une chaine non fermée.
     */
    public function testStringNotClosed()
    {
        $json = $this->stringReader('"xxx');

        $this->expectException(JsonParseException::class);
        $this->expectExceptionMessage('missing closing quote');

        $json->getString();
    }

    /**
     * Fournit des exemples de séquences d'échappement invalides.
     *
     * @return array
     */
    public function invalidEscapeSequenceProvider()
    {
        $tests = [];

        foreach (array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9')) as $letter) {
            $tests[$letter] = ['"\\' . $letter . '"'];
        }

        // Tous les caractères sont invalides, sauf bfnrt/\
        foreach (['b', 'f', 'n', 'r', 't', '/', '\\', ] as $letter) {
            unset($tests[$letter]);
        }

        return $tests;
    }

    /**
     * Vérifie qu'une exception est générée si on a séquence d'échappement invalide.
     *
     * @dataProvider invalidEscapeSequenceProvider
     */
    public function testStringInvalidControlChar($string)
    {
        // Remarque : on n'a pas de message d'erreur spécifique dans ce cas : la chaine n'est pas acceptée par la
        // regexp et donc on a le message "invalid string"
        $json = $this->stringReader($string);

        $this->expectException(JsonParseException::class);
        $this->expectExceptionMessage('bad escape sequence');

        $json->getString();
    }

    /**
     * Fournit des exemples de chaines contenant des séquences unicode mal formées.
     *
     * Dans JsonReader, ces séquences invalides sont détectées par la RegExp
     *
     * @return array
     */
    public function malformedUnicodeSequencesProvider()
    {
        return [
            'un seul chiffre' => ['"\u1"'],
            'deux chiffres' => ['"\u12"'],
            'deux lettres' => ['"\uAA"'],
            'pas hexa' => ['"\ux"'],
            'pas hexa' => ['"\uFGHI"'],
        ];
    }

    /**
     * @dataProvider malformedUnicodeSequencesProvider
     */
    public function testStringWithMalformedUnicodeSequences($string)
    {
        $json = $this->stringReader($string);

        $this->expectException(JsonParseException::class);
        $this->expectExceptionMessage('bad escape sequence');

        $json->getString();
    }

    /**
     * Fournit des exemples de chaines contenant des séquences unicode incorrectes.
     *
     * Dans JsonReader, ces séquences invalides sont détectées par json_decode()
     *
     * @return array
     */
    public function invalidUnicodeSequencesProvider()
    {
        return [
         // 'caractère unicode inexistant' => ['"\uEEEE"'], // non détecté par json_encode !
            'clé de sol mal codée' => ['"\uDD1E\uD834"'],
        ];
    }

    /**
     * @dataProvider invalidUnicodeSequencesProvider
     */
    public function testStringWithInvalidUnicodeSequences($string)
    {
        $json = $this->stringReader($string);

        $this->expectException(JsonParseException::class);
        $this->expectExceptionMessage('Invalid JSON string');

        $json->getString();
    }

    /**
     * Fournit des exemples de fichiers qui contiennent des objets.
     *
     * @return array
     */
    public function objectProvider()
    {
        return [
            ['{}', (object) []],
            ["\t{\n}\r", (object) []],
            ['{"a":1}', (object) ['a' => 1]],
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

        // Initiallement
        $this->assertTrue($json->is('{'));
        $this->assertFalse($json->is('['));
        $this->assertFalse($json->isArray());
        $this->assertFalse($json->isBool());
        $this->assertFalse($json->isEof());
        $this->assertFalse($json->isNull());
        $this->assertFalse($json->isNumber());
        $this->assertTrue($json->isObject());
        $this->assertFalse($json->isString());
        $this->assertTrue($json->isValue());

        // Lit l'objet
        $this->assertEquals($expect, $json->getObject());

        // Eof
        $this->assertTrue($json->isEof());
        $this->assertSame('', $json->getEof());
    }

    /**
     * Fournit des exemples de fichiers qui contiennent des tableaux.
     *
     * @return array
     */
    public function arrayProvider()
    {
        return [
            ['[]', []],
            ["\t[\n]\r", []],
            ['[1]', [1]],
            ['["a"]', ["a"]],
            [' [null, true,"C"]', [null,true,'C']],
            [' [3.14, 4, [], {}]', [3.14, 4, [], (object)[] ]],
            [' [{"the":{"mood":{}}}]', [(object)['the'=>(object)['mood'=>(object)[]]] ]],
            [' [[{"mood":{}}]]', [[ (object)['mood'=>(object)[]]] ]],
            [' [[[{}]]]', [[[(object)[]]]] ],
            [' [[[[]]]]', [[[[ ]]]] ],
        ];
    }

    /**
     * Teste des fichiers json qui contiennent des tableaux.
     *
     * @dataProvider arrayProvider
     */
    public function testArray($json, $expect)
    {
        $json = $this->stringReader($json);

        // Initiallement
        $this->assertFalse($json->is('{'));
        $this->assertTrue($json->is('['));
        $this->assertTrue($json->isArray());
        $this->assertFalse($json->isBool());
        $this->assertFalse($json->isEof());
        $this->assertFalse($json->isNull());
        $this->assertFalse($json->isNumber());
        $this->assertFalse($json->isObject());
        $this->assertFalse($json->isString());
        $this->assertTrue($json->isValue());

        // Lit le tableau
        $this->assertEquals($expect, $json->getArray()); // equals, pas same : on a des objets dans certains tableaux

        // Eof
        $this->assertTrue($json->isEof());
        $this->assertSame('', $json->getEof());
    }

    /**
     * Fournit des exemples de caractères qui ne peuvent pas commencer une valeur.
     *
     * @return array
     */
    public function invalidValueStartProvider()
    {
        return [
            ["'"],
            ['«'],

            ['+'],
            ['.'],
            ['Ø'],

            ['('],
            [')'],

            ['N'],
            ['T'],
            ['F'],

            ['X'],
            ['\\'],
            ['/'],
        ];
    }

    /**
     * Vérifie qu'une exception est générée si on appelle getValue() sur autre chose qu'une valeur.
     *
     * @dataProvider invalidValueStartProvider
     */
    public function testInvalidValueStart($string)
    {
        $json = $this->stringReader($string);

        $this->expectException(JsonParseException::class);
        $this->expectExceptionMessage('unexpected char');

        $json->getValue();
    }

    /**
     * Vérifie qu'une exception est générée si on appelle getEof() alors qu'on n'st pas à la fin.
     *
     * @dataProvider invalidValueStartProvider
     */
    public function testNotEof($string)
    {
        $json = $this->stringReader('x');

        $this->expectException(JsonParseException::class);
        $this->expectExceptionMessage('expected EOF');

        $json->getEof();
    }
}
