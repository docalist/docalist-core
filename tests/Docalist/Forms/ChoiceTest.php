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

use WP_UnitTestCase;
use Docalist\Forms\Choice;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ChoiceTest extends WP_UnitTestCase
{
    /**
     * Crée un élément.
     *
     * @return Choice
     */
    protected function getElement()
    {
        return $this->getMockForAbstractClass(Choice::class, func_get_args());
    }

    public function testGetSetOptions()
    {
        $element = $this->getElement();
        $this->assertSame([], $element->getOptions());

        $options = ['a' => 'A', 'B', 'Group' => ['C', 'D']];

        $element->setOptions($options);
        $this->assertSame($options, $element->getOptions());
    }
}
