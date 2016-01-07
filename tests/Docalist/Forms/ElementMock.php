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

use Docalist\Forms\Element;

/**
 * Classe héritée de Element pour permettre de tester l'API interne.
 */
class ElementMock extends Element
{
    public function setOccurence($occurence)
    {
        return parent::setOccurence($occurence);
    }

    public function getOccurence()
    {
        return parent::getOccurence();
    }

    public function getControlName()
    {
        return parent::getControlName();
    }
}
