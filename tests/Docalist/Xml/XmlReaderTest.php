<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Xml;

use WP_UnitTestCase;
use Docalist\Xml\XmlReader;
use Docalist\Xml\XmlParseException;
use LogicException;
use DOMText, DOMElement;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class XmlReaderTest extends WP_UnitTestCase
{
    public function fileURI($xml)
    {
        return 'data:application/json;charset=UTF-8;base64,'.base64_encode($xml);
    }

    /**
     * Fournit des exemples de source xml vides.
     *
     * @return string[][]
     */
    public function emptyXmlProvider()
    {
        return [
            [''],
            [' '],
            ["\t"],
            ["\n"],
            ["\r"],
            ["\0"],
            ["\x0B"],
        ];
    }

    /**
     * fromString() avec une chaine vide génère une exception.
     *
     * @param $xml
     *
     * @dataProvider emptyXmlProvider
     *
     * @expectedException           Docalist\Xml\XmlParseException
     * @expectedExceptionMessage    Invalid or empy XML string
     *
     */
    public function testFromStringWithEmptyXml($xml)
    {
        XmlReader::fromString($xml);
    }

    /**
     * fromFile() avec un fichier vide génère une exception.
     *
     * @param $xml
     *
     * @dataProvider emptyXmlProvider
     *
     * @expectedException           Docalist\Xml\XmlParseException
     * @expectedExceptionMessage    XML error line 1
     */
    public function testFromFileWithEmptyXml($xml)
    {
        XmlReader::fromFile($this->fileURI($xml));
    }

    /**
     * Fournit des exemples de source xml invalides.
     *
     * @return string[][]
     */
    public function invalidXmlProvider()
    {
        return [
            ['?xml version="1.0" ?>'],
            ['<?xml version="1.0" ?>'],
            ['<?xml version="1.0" ?><a>'],
            ['<a>'],
            ['<a></b>'],
        ];
    }

    /**
     * fromString() avec du xml invalide génère une exception.
     *
     * @param $xml
     *
     * @dataProvider invalidXmlProvider
     *
     * @expectedException           Docalist\Xml\XmlParseException
     * @expectedExceptionMessage    XML error line 1
     */
    public function testFromStringWithInvalidXml($xml)
    {
        XmlReader::fromString($xml);
    }

    /**
     * fromFile() avec du xml invalide génère une exception.
     *
     * @param $xml
     *
     * @dataProvider invalidXmlProvider
     *
     * @expectedException           Docalist\Xml\XmlParseException
     * @expectedExceptionMessage    XML error line 1
     */
    public function testFromFileWithInvalidXml($xml)
    {
        XmlReader::fromFile($this->fileURI($xml))->enter('a');
    }


    /**
     * fromFile() avec un fichier inexistant génère une exception.
     *
     * @param $xml
     *
     * @expectedException           InvalidArgumentException
     * @expectedExceptionMessage    Unable to open XML file
     */
    public function testFromFileWithInexistentFile()
    {
        XmlReader::fromFile('/do/not/exists.xml');
    }

    /**
     * Fournit des exemples de fichiers xml contenant juste un tag vide.
     *
     * @return string[][]
     */
    public function emptyTagProvider()
    {
        return [
            ['<a />'],
            ["  \n\t\n  <a />  \n\t\n  ", 3],
            ['<a></a>'],
            ['<?xml version="1.0" ?><a></a>'],
            ["<?xml version=\"1.0\" ?>\n<a></a>", 2],
        ];
    }

    /**
     * Teste XmlReader avec un source xml contenant juste un tag 'a' vide.
     *
     * @dataProvider emptyTagProvider
     */
    public function testEmptyTag($xml, $lineNumber = 1)
    {
        $xml = XmlReader::fromString($xml);

        $this->assertFalse($xml->isEof());

        $this->assertTrue($xml->is('a'));
        $this->assertTrue($xml->is('a'), $xml::OPEN_TAG);
        $this->assertTrue($xml->is('a'), $xml::CLOSE_TAG); // true car c'est un tag vide

        $this->assertFalse($xml->is('b'), $xml::OPEN_TAG);
        $this->assertFalse($xml->is('b'), $xml::CLOSE_TAG);

        $this->assertSame($lineNumber, $xml->getCurrentLineNumber());

        $this->assertSame('<a/>', $xml->getOuterXml());

        $xml->enter('a');
        $xml->leave('a');

        $this->assertTrue($xml->isEof());
    }

    /**
     * Teste enter() et leave()
     */
    public function testEnterLeave()
    {
        $xml = '
            <file>
                <info date="date" />
                <records>
                    <record id="1"><title>yeah</title></record>
                    <record id="2">...</record>
                </records>
            </file>';

        $xml = XmlReader::fromString($xml);
        $this->assertSame($xml, $xml->enter('file'));

        $this->assertSame($xml, $xml->enter('info'));
        $this->assertSame('', $xml->getOuterXml()); // NON ! le contenu du tag info c'est ""
        $this->assertSame($xml, $xml->leave('info'));

        $this->assertSame($xml, $xml->enter('records'));

        $this->assertSame($xml, $xml->enter('record'));
        $this->assertSame('<title>yeah</title>', $xml->getOuterXml());
        $this->assertSame($xml, $xml->leave('record'));

        $this->assertSame($xml, $xml->leave('records'));
        $this->assertSame($xml, $xml->leave('file'));

    }

    /**
     * Une exception est générée si on appelle leave() sans avoir appellé enter().
     *
     * @expectedException           LogicException
     * @expectedExceptionMessage    Call to leave('a') without enter('a') before
     */
    public function testLeaveWithoutEnter()
    {
        $xml = XmlReader::fromString('<a></a>');
        $xml->leave('a');
    }

    /**
     * Une exception est générée si on appelle leave() avec un tag différent de celui indiqué pour enter().
     *
     * @expectedException           LogicException
     * @expectedExceptionMessage    Call to leave('b') in tag <a>
     */
    public function testEnterLeaveMisMatch1()
    {
        $xml = XmlReader::fromString('<a></a>');
        $xml->enter('a');
        $xml->leave('b');
    }

    /**
     * Une exception est générée si on appelle leave() avec un tag différent de celui indiqué pour enter().
     *
     * @expectedException           LogicException
     * @expectedExceptionMessage    Call to leave('a') in tag <b>
     */
    public function testEnterLeaveMisMatch2()
    {
        $xml = XmlReader::fromString('<a><b></b></a>');
        $xml->enter('a');
        $xml->enter('b');
        $xml->leave('a');
    }

    /**
     * Une exception est générée si on appelle leave() avec un tag différent de celui indiqué pour enter().
     *
     * @expectedException           LogicException
     * @expectedExceptionMessage    Call to leave('b') in empty tag <a/>
     */
    public function testEnterLeaveMisMatch3()
    {
        $xml = XmlReader::fromString('<a/>');
        $xml->enter('a');
        $xml->leave('b');
    }

    /**
     * Teste la méthode next().
     */
    public function testNext()
    {
        $xml = '<?xml version="1.0"?>
            <!-- file.xml -->
            <?php echo "on" ?>
            <file>
                bla bla
                <!-- <info>...</info> -->
                <records count="2">
                    <sortedBy>id</sortedBy>
                    <extra-info1>done</extra-info1>
                    <record id="1"><title>this is <b>a title</b>yeah</title></record>
                    <extra-info2>done</extra-info2>
                    <record id="2">text1<tag>text2</tag>text3</record>
                    <extra-info3>done</extra-info3>
                </records>
                <record id="3">...</record>

            </file>';

        $xml = XmlReader::fromString($xml);
        $this->assertTrue($xml->next('file')); // ignore le commentaire et la PI
        $this->assertSame($xml, $xml->enter('file'));

        $this->assertTrue($xml->next('records')); // passe le texte "bla bla" et le commentaire
        $this->assertSame($xml, $xml->enter('records'));

        $this->assertTrue($xml->next('record')); // passe sortedby et extra-info1
        $this->assertSame('<record id="1"><title>this is <b>a title</b>yeah</title></record>', $xml->getOuterXml());

        $this->assertTrue($xml->next('record')); // extra-info2
        $this->assertSame('<record id="2">text1<tag>text2</tag>text3</record>', $xml->getOuterXml());

        $this->assertFalse($xml->next('record')); // passe extra-info3, ne doit pas trouver record id=3

        $this->assertFalse($xml->next('quelconque')); // on est sur le </records>, ne doit plus rien trouver
        $this->assertSame($xml, $xml->leave('records'));

        $this->assertTrue($xml->next('record')); // là on doit le trouver
        $this->assertSame('<record id="3">...</record>', $xml->getOuterXml());

        $this->assertSame($xml, $xml->leave('file'));

        $this->assertTrue($xml->isEof());
    }

    /**
     * Une exception est générée si on appelle mustBe() et que le type ou le nom ne correspondent pas.
     *
     * @expectedException           Docalist\Xml\XmlParseException
     * @expectedExceptionMessage    XML error line 1: expected start tag <b>
     */
    public function testMustBeFails1()
    {
        XmlReader::fromString('<a></a>')->mustBe('b', XmlReader::OPEN_TAG);
    }

    /**
     * Une exception est générée si on appelle mustBe() et que le type ou le nom ne correspondent pas.
     *
     * @expectedException           Docalist\Xml\XmlParseException
     * @expectedExceptionMessage    XML error line 1: expected end tag </b>
     */
    public function testMustBeFails2()
    {
        XmlReader::fromString('<a></a>')->mustBe('b', XmlReader::CLOSE_TAG);
    }

    /**
     * Une exception est générée si on appelle mustBe() et que le type ou le nom ne correspondent pas.
     *
     * @expectedException           Docalist\Xml\XmlParseException
     * @expectedExceptionMessage    XML error line 1: expected node of type 8 with name "b"
     */
    public function testMustBeFails3()
    {
        XmlReader::fromString('<a></a>')->mustBe('b', XmlReader::COMMENT);
    }

    /**
     * Fournit des exemples de source pour testGetOuterXml.
     *
     * @return string[][]
     */
    public function outerXmlProvider()
    {
        return [
            ['<a/>', ''],
            ['<a />', '<a/>'],
            ['<a></a>', '<a/>'],
            ['<a>hello</a>', '<a>hello</a>'],
            ['<a>hello <b>world</b>!</a>', ''],
            ['<a>hello <!-- start bold --><b>world</b><!-- end bold -->!</a>', ''],
            ['<a>hello <?php echo "bold" ?><b>world</b><?php echo "/bold" ?>!</a>', ''],
            ['<a>hello <![CDATA[world]]>!</a>', '<a>hello world!</a>'],
            ['<a>hello <![CDATA[<b>world</b>]]>!</a>', '<a>hello &lt;b&gt;world&lt;/b&gt;!</a>'],
            ['<a id="13"></a>', '<a id="13"/>'],
            ['<a id = "13" ></a>', '<a id="13"/>'],
        ];
    }

    /**
     * Teste getOuterXml()
     *
     * @param $xml
     * @param $expected
     *
     * @dataProvider outerXmlProvider
     */
    public function testGetOuterXml($xml, $expected)
    {   empty($expected) && $expected = $xml;
        $xml = XmlReader::fromString($xml);
        $xml->next();
        $this->assertSame($expected, $xml->getOuterXml());
    }

    /**
     * Fournit des exemples de source pour testGetInnerXml.
     *
     * @return string[][]
     */
    public function innerXmlProvider()
    {
        return [
            ['<a/>', ''],
            ['<a />', ''],
            ['<a></a>', ''],
            ['<a>hello</a>', 'hello'],
            ['<a>hello <b>world</b>!</a>', 'hello <b>world</b>!'],
            ['<a>hello <!-- start --><b>world</b><!-- end -->!</a>', 'hello <!-- start --><b>world</b><!-- end -->!'],
            ['<a>hello <?php echo ?><b>world</b><?php echo ?>!</a>', 'hello <?php echo ?><b>world</b><?php echo ?>!'],
            ['<a>hello <![CDATA[world]]>!</a>', 'hello world!'],
            ['<a>hello <![CDATA[<b>world</b>]]>!</a>', 'hello &lt;b&gt;world&lt;/b&gt;!'],
            ['<a id="13"></a>', ''],
            ['<a id = "13" ></a>', ''],
        ];
    }

    /**
     * Teste getInnerXml()
     *
     * @param $xml
     * @param $expected
     *
     * @dataProvider innerXmlProvider
     */
    public function testGetInnerXml($xml, $expected)
    {
        $xml = XmlReader::fromString($xml);
        $xml->next();
        $this->assertSame($expected, $xml->getInnerXml());
    }

    /**
     * Fournit des exemples de source pour testGetText.
     *
     * @return string[][]
     */
    public function getTextProvider()
    {
        return [
            ['<a/>', ''],
            ['<a></a>', ''],
            ['<a>hello</a>', 'hello'],
            ['<a>hello <b>world</b>!</a>', 'hello world!'],
            ['<a>hello <!-- start bold --><b>world</b><!-- end bold -->!</a>', 'hello world!'],
            ['<a>hello <?php echo "bold" ?><b>world</b><?php echo "/bold" ?>!</a>', 'hello world!'],
            ['<a>hello <![CDATA[world]]>!</a>', 'hello world!'],
            ['<a>hello <![CDATA[<b>world</b>]]>!</a>', 'hello <b>world</b>!'],
            ['<a id="13"></a>', ''],
        ];
    }

    /**
     * Teste getText().
     *
     * @param $xml
     * @param $expected
     *
     * @dataProvider getTextProvider
     */
    public function testGetText($xml, $expected)
    {
        $xml = XmlReader::fromString($xml);
        $xml->next();
        $this->assertSame($expected, $xml->getText());
    }

    public function testGetTagName()
    {
        $xml = XmlReader::fromString('<a><b/></a>');
        $xml->next();
        $this->assertSame('a', $xml->getTagName());

        $xml->enter('a');
        $this->assertSame('b', $xml->getTagName());

        $xml->enter('b');
        $this->assertSame('b', $xml->getTagName());
        $xml->leave('b');

        $this->assertSame('a', $xml->getTagName());
        $xml->leave('a');

        $this->assertSame('', $xml->getTagName()); // EOF
    }

    /**
     * Fournit des exemples pour testGetCurrentLine.
     *
     * @return string[][]
     */
    public function getCurrentLineProviderNumber()
    {
        return [
            ["<a/>", 1],
            ["\n<a/>", 2],
            ["\n\n<a/>", 3],
            ["\n\n<a/>", 3],
            ["<?xml version=\"1.0\" ?><a/>", 1],
            ["<?xml version=\"1.0\" ?>\n<a/>", 2],
            ["<?xml version=\"1.0\" ?>\n\n<a/>", 3],
            ["<?xml \nversion=\"1.0\" \n?><a/>", 3],
        ];
    }

    /**
     * Teste getGetCurrentLine().
     *
     * @param $xml
     * @param $expected
     *
     * @dataProvider getCurrentLineProviderNumber
     */
    public function testGetCurrentLineNumber($xml, $expected)
    {
        $xml = XmlReader::fromString($xml);
        $xml->next();
        $this->assertSame($expected, $xml->getCurrentLineNumber());
    }

    /**
     * Teste getGetCurrentLine() en fin de fichier.
     */
    public function testGetCurrentLineNumberAtEof()
    {
        $xml = XmlReader::fromString('<a/>');
        $xml->next();
        $xml->next();
        $this->assertSame(0, $xml->getCurrentLineNumber());
    }

    public function testGetNode()
    {
        $xml = XmlReader::fromString('
            <a>
                <b id="1">value</b>
                <c />
            </a>');

        $xml->next();
        $a = $xml->getNode(); /** @var DOMElement $a */
        $this->assertSame('a', $a->tagName);

        $xml->enter('a');
        $xml->next();

        $b = $xml->getNode(); /** @var DOMElement $b */
        $this->assertSame('b', $b->tagName);
        $this->assertSame('value', $b->nodeValue);
        $this->assertSame('value', $b->textContent);
        $this->assertTrue($b->hasChildNodes());
        $this->assertSame(1, $b->childNodes->length);

        $this->assertTrue($b->hasAttributes());
        $this->assertSame(1, $b->attributes->length);

        $xml->next();

        $c = $xml->getNode(); /** @var DOMElement $c */
        $this->assertSame('c', $c->tagName);
        $this->assertSame('', $c->nodeValue);
        $this->assertSame('', $c->textContent);

        $xml->enter('c');
        $emptycontent = $xml->getNode(); /** @var DOMText $emptyContent */
        $this->assertTrue($emptycontent instanceOf DOMText);
        $this->assertSame(null, $emptycontent->nodeValue);
        $this->assertSame('', $emptycontent->textContent);

        $xml->leave('c');

        $xml->leave('a');

        $xml->next();
        $this->assertTrue($xml->isEof());
    }

    /**
     * fromFile() avec du xml invalide génère une exception.
     *
     * @param $xml
     *
     * @dataProvider invalidXmlProvider
     *
     * @expectedException           LogicException
     * @expectedExceptionMessage    Call to getNode() at EOF
     */
    public function testGetNodeAtEnd()
    {
        $xml = XmlReader::fromString('<a></a>');

        $xml->next(); // passe <a>

        $xml->next(); // on est à la fin
        $a = $xml->getNode(); // exception
    }
}
