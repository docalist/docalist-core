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
 * Un bouton de type submit.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#the-button-element The button element}.
 */
class Submit extends Button
{
    protected $attributes = ['type' => 'submit'];

    // Pour un élément Button, la valeur par défaut de type est "submit", donc en théorie, inutile d'avoir
    // l'attribut type="submit". Sauf que sous IE, ça ne marche pas, donc on le génère systématiquement.
}
