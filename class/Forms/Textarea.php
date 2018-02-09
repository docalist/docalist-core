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
 * Une zone de texte multiligne.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#the-textarea-element The textarea element}.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Textarea extends Element
{
    protected $attributes = ['rows' => 10, 'cols' => 50];
}
