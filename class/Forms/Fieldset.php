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
 * Un champ input de type hidden.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#the-fieldset-element The
 * fieldset element}.
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
