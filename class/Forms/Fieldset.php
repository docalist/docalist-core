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
 * Un champ input de type hidden.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#the-fieldset-element The fieldset element}.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Fieldset extends Container
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
