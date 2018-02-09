<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Forms;

/**
 * Une metabox wordpress.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Metabox extends Container
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
