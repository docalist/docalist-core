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

namespace Docalist\Tests\Forms;

use Docalist\Forms\Div;
use Docalist\Forms\Select;
use Docalist\Tests\DocalistTestCase;
use InvalidArgumentException;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SelectTest extends DocalistTestCase
{
    public function testGetSetFirstOption(): void
    {
        $default = ['' => '…'];
        $select = new Select();

        $this->assertSame($default, $select->getFirstOption());

        $select->setFirstOption(false);
        $this->assertSame(false, $select->getFirstOption());

        $select->setFirstOption(true);
        $this->assertSame($default, $select->getFirstOption());

        $select->setFirstOption(false);
        $select->setFirstOption(true);
        $this->assertSame($default, $select->getFirstOption());

        $select->setFirstOption('choose');
        $this->assertSame(['' => 'choose'], $select->getFirstOption());

        $select->setFirstOption([-1 => 'na']);
        $this->assertSame([-1 => 'na'], $select->getFirstOption());
    }

    /**
     * Teste setFirstOption avec un tableau vide.
     */
    public function testSetFirstOptionEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('array must contain one item');

        (new Select())->setFirstOption([]);
    }

    /**
     * Teste setFirstOption avec un tableau de plus d'un élément.
     */
    public function testSetFirstOptionBadArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('array must contain one item');

        // @phpstan-ignore-next-line
        (new Select())->setFirstOption([1, 2]);
    }

    /**
     * Teste setFirstOption avec une mauvaise valeur.
     */
    public function testSetFirstOptionBadArg(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid firstOption');

        (new Select())->setFirstOption(['a', 'b']);
    }

    public function testGetControlName(): void
    {
        $select = new Select('s');
        $this->assertSame('s', $this->callNonPublic($select, 'getControlName'));

        $select->setAttribute('multiple');
        $this->assertSame('s[]', $this->callNonPublic($select, 'getControlName'));

        $select = new Select('s', [], new Div('parent'));
        $this->assertSame('parent[s]', $this->callNonPublic($select, 'getControlName'));

        $select->setAttribute('multiple');
        $this->assertSame('parent[s][]', $this->callNonPublic($select, 'getControlName'));
    }

    public function testIsMultivalued(): void
    {
        $select = new Select('s');
        $this->assertFalse($this->callNonPublic($select, 'isMultivalued'));

        $select->setAttribute('multiple');
        $this->assertTrue($this->callNonPublic($select, 'isMultivalued'));

        $select = new Select('s');
        $select->setRepeatable();
        $this->assertTrue($this->callNonPublic($select, 'isMultivalued'));
        $select->setAttribute('multiple');
        $this->assertTrue($this->callNonPublic($select, 'isMultivalued'));
    }

    public function testRender(): void
    {
        $select = new Select();
        $select->setFirstOption(false);
        $expected =
            '<select name="" class="select">'.
            '</select>';
        $this->assertSame($expected, $select->render('xhtml'));

        $select->setFirstOption(true);
        $expected =
            '<select name="" class="select">'.
                '<option value="">…</option>'.
            '</select>';
        $this->assertSame($expected, $select->render('xhtml'));

        $select = new Select('s');
        $select->setAttribute('multiple');
        $select->setFirstOption(false);
        $expected =
            '<select name="s[]" multiple="multiple" class="select">'.
            '</select>';
        $this->assertSame($expected, $select->render('xhtml'));
    }

    public function testRenderOptions(): void
    {
        $select = new Select('s');
        $select->setFirstOption(false);
        $select->setOptions([1 => 'a', 2 => 'b']);
        $expected =
            '<select name="s" class="select">'.
                '<option value="1">a</option>'.
                '<option value="2">b</option>'.
            '</select>';
        $this->assertSame($expected, $select->render('xhtml'));

        $select->setFirstOption(true);
        $expected =
            '<select name="s" class="select">'.
                '<option value="">…</option>'.
                '<option value="1">a</option>'.
                '<option value="2">b</option>'.
            '</select>';
        $this->assertSame($expected, $select->render('xhtml'));

        $select->setAttribute('multiple');

        $select->bind(2);
        $expected =
            '<select name="s[]" class="select" multiple="multiple">'.
                '<option value="1">a</option>'.
                '<option value="2" selected="selected">b</option>'.
            '</select>';
        $this->assertSame($expected, $select->render('xhtml'));

        $select->bind([2, 3]);
        $expected =
            '<select name="s[]" class="select" multiple="multiple">'.
                '<option value="1">a</option>'.
                '<option value="2" selected="selected">b</option>'.
                '<option value="3" selected="selected" class="select-invalid-entry" title="Option invalide">Invalide : 3</option>'.
            '</select>';
        $this->assertSame($expected, $select->render('xhtml'));

        $select->setRepeatable();
        $select->removeAttribute('multiple');
        $select->setFirstOption(false);

        $select->bind([[2, 3]]);
        $expected =
            '<select name="s[0]" class="select">'.
                '<option value="1">a</option>'.
                '<option value="2" selected="selected">b</option>'.
                '<option value="3" selected="selected" class="select-invalid-entry do-not-clone" title="Option invalide">Invalide : 3</option>'.
            '</select>'.
            ' <button type="button" class="cloner button button-link"><span class="dashicons-before dashicons-plus-alt"></span></button>';
        $this->assertSame($expected, $select->render('xhtml'));
    }

    public function testRenderOptionsGroups(): void
    {
        $select = new Select('s');
        $select->setFirstOption(false);
        $select->setOptions([
            'odd'  => [
                1 => 'one',
                3 => 'three',
            ],
            'even' => [
                2 => 'two',
                4 => 'four',
            ],
        ]);
        $expected =
            '<select name="s" class="select">'.
                '<optgroup label="odd">'.
                    '<option value="1">one</option>'.
                    '<option value="3">three</option>'.
                '</optgroup>'.
                '<optgroup label="even">'.
                    '<option value="2">two</option>'.
                    '<option value="4">four</option>'.
                '</optgroup>'.
            '</select>';
        $this->assertSame($expected, $select->render('xhtml'));
    }
}
