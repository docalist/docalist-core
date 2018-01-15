<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Forms;

/**
 * Un block div.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Div extends Container
{
    protected function hasLayout()
    {
        return false;
    }

    protected function hasDescriptionBlock()
    {
        return false;
    }
}
