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
 * Un bouton radio.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#radio-button-state-(type=radio) input type=radio}.
 */
class Radio extends Checkbox
{
    protected $attributes = ['type' => 'radio'];
}
