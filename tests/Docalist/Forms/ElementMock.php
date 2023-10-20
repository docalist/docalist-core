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

use Docalist\Forms\Element;

/**
 * Classe héritée de Element pour permettre de tester l'API interne.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ElementMock extends Element
{
    public function setOccurence(int|string $occurence): void
    {
        parent::setOccurence($occurence);
    }

    // public function getOccurence()
    // {
    //     return parent::getOccurence();
    // }

    public function getControlName(): string
    {
        return parent::getControlName();
    }
}
