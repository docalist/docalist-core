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
 * Une case à cocher.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#checkbox-state-(type=checkbox) input type=checkbox}.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Checkbox extends Input
{
    protected $attributes = ['type' => 'checkbox', 'value' => 1];

    /**
     * Indique au containeur qu'il ne doit pas générer de bloc description.
     *
     * Pour un checkbox unique, la description est utilisée comme un second label :
     *
     *     label : [X] description.
     *
     * @return bool
     */
    protected function hasDescriptionBlock()
    {
        return false;
    }
}
