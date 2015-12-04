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
namespace Docalist\Tests\Forms;

use WP_UnitTestCase;
use Docalist\Forms\Form;

class FormTest extends WP_UnitTestCase
{
    public function testConstruct()
    {
        $form = new Form();
        $this->assertSame(['action' => '', 'method' => 'post'], $form->getAttributes());

        $form = new Form('../submit.php', 'GET');
        $this->assertSame(['action' => '../submit.php', 'method' => 'GET'], $form->getAttributes());
    }
}
