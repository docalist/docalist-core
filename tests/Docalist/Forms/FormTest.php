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
use Docalist\Tests\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class FormTest extends DocalistTestCase
{
    public function testConstruct(): void
    {
        $form = new Form();
        $this->assertSame(['action' => '', 'method' => 'post'], $form->getAttributes());

        $form = new Form('../submit.php', 'GET');
        $this->assertSame(['action' => '../submit.php', 'method' => 'GET'], $form->getAttributes());
    }
}
