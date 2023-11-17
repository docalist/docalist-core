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

use Docalist\Forms\Form;
use Docalist\Forms\Radiolist;
use Docalist\Tests\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class RadiolistTest extends DocalistTestCase
{
    public function testIsLabelable(): void
    {
        $this->assertFalse($this->callNonPublic(new Radiolist(), 'isLabelable'));
    }

    public function testRender1(): void
    {
        $radiolist = new Radiolist();
        $form = new Form();
        $form->add($radiolist);

        $expected =
            '<form action="" method="post">'.
                '<table class="form-table">'.
                    '<tr>'.
                        '<th></th>'.
                        '<td>'.
                            '<ul class="radiolist">'.
                            '</ul>'.
                        '</td>'.
                    '</tr>'.
                '</table>'.
            '</form>';
        $this->assertSame($expected, $form->render('xhtml'));
    }

    public function testRender2(): void
    {
        $radiolist = new Radiolist();
        $radiolist->setOptions([
            'one' => 'One',
            2     => 'Two',
        ]);
        $form = new Form();
        $form->add($radiolist);
        $expected =
        '<form action="" method="post">'.
            '<table class="form-table">'.
                '<tr>'.
                    '<th></th>'.
                    '<td>'.
                        '<ul class="radiolist">'.
                            '<li><label><input name="" type="radio" value="one"/> One</label></li>'.
                            '<li><label><input name="" type="radio" value="2"/> Two</label></li>'.
                        '</ul>'.
                    '</td>'.
                '</tr>'.
            '</table>'.
        '</form>';

        $this->assertSame($expected, $form->render('xhtml'));
    }

    public function testRender3(): void
    {
        $radiolist = new Radiolist();
        $radiolist->setOptions([
            'one'   => 'One',
            2       => 'Two',
            'group' => ['a' => 'A', 'b' => 'B'],
        ]);
        $form = new Form();
        $form->add($radiolist);
        $expected =
        '<form action="" method="post">'.
            '<table class="form-table">'.
                '<tr>'.
                    '<th></th>'.
                    '<td>'.
                        '<ul class="radiolist">'.
                            '<li><label><input name="" type="radio" value="one"/> One</label></li>'.
                            '<li><label><input name="" type="radio" value="2"/> Two</label></li>'.
                            '<li class="radiolist-group">'.
                                '<p class="radiolist-group-label">group</p>'.
                                '<ul>'.
                                    '<li><label><input name="" type="radio" value="a"/> A</label></li>'.
                                    '<li><label><input name="" type="radio" value="b"/> B</label></li>'.
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
        $radiolist = new Radiolist();
        $radiolist->setOptions([
            'one' => 'One',
            2     => 'Two',
        ]);
        $radiolist->bind('three');
        $form = new Form();
        $form->add($radiolist);
        $expected =
        '<form action="" method="post">'.
            '<table class="form-table">'.
                '<tr>'.
                    '<th></th>'.
                    '<td>'.
                        '<ul class="radiolist">'.
                            '<li><label><input name="" type="radio" value="one"/> One</label></li>'.
                            '<li><label><input name="" type="radio" value="2"/> Two</label></li>'.
                            '<li class="radiolist-invalid-entry" title="Option invalide"><label><input name="" type="radio" value="three" checked="checked"/> three</label></li>'.
                        '</ul>'.
                    '</td>'.
                '</tr>'.
            '</table>'.
        '</form>';

        $this->assertSame($expected, $form->render('xhtml'));
    }

    public function testRender5(): void // idem 4 avec repeatable
    {
        $radiolist = new Radiolist();
        $radiolist->setOptions([
            'one' => 'One',
            2     => 'Two',
        ]);
        $radiolist->setRepeatable(true);
        $radiolist->bind(['three']);
        $form = new Form();
        $form->add($radiolist);
        $expected =
        '<form action="" method="post">'.
            '<table class="form-table">'.
                '<tr>'.
                    '<th></th>'.
                    '<td>'.
                        '<ul class="radiolist">'.
                            '<li><label><input name="" type="radio" value="one"/> One</label></li>'.
                            '<li><label><input name="" type="radio" value="2"/> Two</label></li>'.
                            '<li class="radiolist-invalid-entry do-not-clone" title="Option invalide"><label><input name="" type="radio" value="three" checked="checked"/> three</label></li>'.
                        '</ul>'.
                    '</td>'.
                '</tr>'.
            '</table>'.
        '</form>';

        $this->assertSame($expected, $form->render('xhtml'));
    }
}
