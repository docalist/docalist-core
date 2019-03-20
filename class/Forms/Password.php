<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Forms;

/**
 * Un champ input de type password.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#password-state-(type=password) input type=password}.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Password extends Input
{
    protected $attributes = ['type' => 'password'];
}
