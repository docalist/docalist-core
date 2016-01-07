<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
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
 * Une case à cocher.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#checkbox-state-(type=checkbox)
 * input type=checkbox}.
 */
class Checkbox extends Input
{
    protected $attributes = ['type' => 'checkbox', 'value' => 1];

    /**
     * Pour un checkbox unique, la description est utilisée comme un second label :
     * label : [ ] description.
     * Indique au containeur qu'il ne doit pas générer de bloc description.
     *
     * @return bool
     */
    protected function hasDescriptionBlock()
    {
        return false;
    }
}
