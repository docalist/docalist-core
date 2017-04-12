<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Forms
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Forms;

/**
 * Une metabox wordpress.
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
