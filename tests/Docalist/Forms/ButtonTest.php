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

use Docalist\Forms\Button;
use Docalist\Forms\Form;
use Docalist\Test\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ButtonTest extends DocalistTestCase
{
    public function testConstruct(): void
    {
        $button = new Button();
        $this->assertSame('', $button->getLabel());
    }

    public function testGetType(): void
    {
        $this->assertSame('button', (new Button())->getAttribute('type'));
    }

    public function testHasLabelBlock(): void
    {
        $this->assertFalse($this->callNonPublic(new Button(), 'hasLabelBlock'));
    }

    public function testRender1(): void
    {
        $button = new Button();
        $expected =
            '<button name="" type="button">'.
            '</button>';
        $this->assertSame($expected, $button->render('xhtml'));
    }

    public function testRender2(): void
    {
        $button = new Button('mylabel', 'myname', ['class' => 'btn btn-small']);
        $expected =
            '<button name="myname" type="button" class="btn btn-small">'.
                'mylabel'.
            '</button>';
        $this->assertSame($expected, $button->render('xhtml'));
    }

    public function testRender3(): void
    {
        $button = new Button('mylabel', 'myname', ['class' => 'btn btn-small']);
        $form = new Form();
        $form->add($button);
        $expected =
            '<form action="" method="post">'.
                '<table class="form-table">'.
                    '<tr class="field-myname-group">'.
                        '<th>'.
                        '</th>'.
                        '<td>'.
                            '<button name="myname" type="button" class="btn btn-small">'.
                                'mylabel'.
                            '</button>'.
                        '</td>'.
                    '</tr>'.
                '</table>'.
            '</form>';
        $this->assertSame($expected, $form->render('xhtml'));
    }
}
