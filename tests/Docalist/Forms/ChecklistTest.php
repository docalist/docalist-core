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

use Docalist\Forms\Choice;
use Docalist\Forms\Form;
use Docalist\Forms\Checklist;
use Docalist\Forms\Theme;
use Docalist\Tests\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ChecklistTest extends DocalistTestCase
{
    public function testIsLabelable(): void
    {
        $this->assertFalse($this->callNonPublic(new Checklist(), 'isLabelable'));
    }

    public function testIsMultivalued(): void
    {
        $this->assertTrue($this->callNonPublic(new Checklist(), 'isMultivalued'));
    }

    public function testRender1(): void
    {
        $checklist = new Checklist();
        $form = new Form();
        $form->add($checklist);

        $expected =
            '<form action="" method="post">'.
                '<table class="form-table">'.
                    '<tr>'.
                        '<th></th>'.
                        '<td>'.
                            '<ul class="checklist">'.
                            '</ul>'.
                        '</td>'.
                    '</tr>'.
                '</table>'.
            '</form>';
        $this->assertSame($expected, $form->render('xhtml'));
    }

    public function testRender2(): void
    {
        $checklist = new Checklist('list');
        $checklist->setOptions([
            'one' => 'One',
            2 => 'Two',
        ]);
        $form = new Form();
        $form->add($checklist);
        $expected =
        '<form action="" method="post">'.
            '<table class="form-table">'.
                '<tr class="field-list-group">'.
                    '<th><label>list</label></th>'.
                    '<td>'.
                        '<ul class="checklist">'.
                            '<li><label><input name="list[]" type="checkbox" value="one"/> One</label></li>'.
                            '<li><label><input name="list[]" type="checkbox" value="2"/> Two</label></li>'.
                        '</ul>'.
                    '</td>'.
                '</tr>'.
            '</table>'.
        '</form>';


        $this->assertSame($expected, $form->render('xhtml'));
    }

    public function testRender3(): void
    {
        $checklist = new Checklist('list');
        $checklist->setOptions([
            'one' => 'One',
            2 => 'Two',
            'group' => ['a' => 'A', 'b' => 'B']
        ]);
        $form = new Form();
        $form->add($checklist);
        $expected =
        '<form action="" method="post">'.
            '<table class="form-table">'.
                '<tr class="field-list-group">'.
                    '<th><label>list</label></th>'.
                    '<td>'.
                        '<ul class="checklist">'.
                            '<li><label><input name="list[]" type="checkbox" value="one"/> One</label></li>'.
                            '<li><label><input name="list[]" type="checkbox" value="2"/> Two</label></li>'.
                            '<li class="checklist-group">'.
                                '<p class="checklist-group-label">group</p>'.
                                '<ul>'.
                                    '<li><label><input name="list[]" type="checkbox" value="a"/> A</label></li>'.
                                    '<li><label><input name="list[]" type="checkbox" value="b"/> B</label></li>'.
                                '</ul>'.
                            '</li>'.
                        '</ul>'.
                    '</td>'.
                '</tr>'.
            '</table>'.
        '</form>';


        $this->assertSame($expected, $form->render('xhtml'));
    }

    public function testRender4(): void
    {
        $checklist = new Checklist('list');
        $checklist->setOptions([
            'one' => 'One',
            2 => 'Two',
        ]);
        $checklist->bind('three');
        $form = new Form();
        $form->add($checklist);
        $expected =
        '<form action="" method="post">'.
            '<table class="form-table">'.
                '<tr class="field-list-group">'.
                    '<th><label>list</label></th>'.
                    '<td>'.
                        '<ul class="checklist">'.
                            '<li><label><input name="list[]" type="checkbox" value="one"/> One</label></li>'.
                            '<li><label><input name="list[]" type="checkbox" value="2"/> Two</label></li>'.
                            '<li class="checklist-invalid-entry" title="Option invalide"><label><input name="list[]" type="checkbox" value="three" checked="checked"/> three</label></li>'.
                        '</ul>'.
                    '</td>'.
                '</tr>'.
            '</table>'.
        '</form>';


        $this->assertSame($expected, $form->render('xhtml'));
    }

    public function testRender5(): void // idem 4 avec repeatable
    {
        $checklist = new Checklist('list');
        $checklist->setOptions([
            'one' => 'One',
            2 => 'Two',
        ]);
        $checklist->setRepeatable(true);
        $checklist->bind(['three']);
        $form = new Form();
        $form->add($checklist);
        $expected =
        '<form action="" method="post">'.
            '<table class="form-table">'.
                '<tr class="field-list-group">'.
                    '<th><label>list</label></th>'.
                    '<td>'.
                        '<ul class="checklist">'.
                            '<li><label><input name="list[0][]" type="checkbox" value="one"/> One</label></li>'.
                            '<li><label><input name="list[0][]" type="checkbox" value="2"/> Two</label></li>'.
                            '<li class="checklist-invalid-entry do-not-clone" title="Option invalide"><label><input name="list[0][]" type="checkbox" value="three" checked="checked"/> three</label></li>'.
                        '</ul>'.
                        ' <button type="button" class="cloner button button-link"><span class="dashicons-before dashicons-plus-alt"></span></button>'.
                    '</td>'.
                '</tr>'.
            '</table>'.
        '</form>';


        $this->assertSame($expected, $form->render('xhtml'));
    }

    /**
     * Bug 16/10/2023 : si le contrôle n'a pas de nom, on se retrouve avec des attributs name="[]" comme le champ a isMultivalued() à true
     */
    public function testBugNoName(): void
    {
        $checklist = new Checklist();
        $checklist->setOptions([
            'one' => 'One',
            2 => 'Two',
        ]);
        $form = new Form();
        $form->add($checklist);
        $expected =
        '<form action="" method="post">'.
            '<table class="form-table">'.
                '<tr>'.
                    '<th></th>'.
                    '<td>'.
                        '<ul class="checklist">'.
                            '<li><label><input name="" type="checkbox" value="one"/> One</label></li>'.
                            '<li><label><input name="" type="checkbox" value="2"/> Two</label></li>'.
                        '</ul>'.
                    '</td>'.
                '</tr>'.
            '</table>'.
        '</form>';


        $this->assertSame($expected, $form->render('xhtml'));
    }
}
