<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Forms;

/**
 * Un champ input de type texte.
 *
 * Référence W3C :
 * {@link
 * http://www.w3.org/TR/html5/forms.html#text-(type=text)-state-and-search-state-(type=search) input type=search}.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Input extends Element
{
    protected $attributes = ['type' => 'text'];
}
