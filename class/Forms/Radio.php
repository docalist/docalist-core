<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2023 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Forms;

/**
 * Un bouton radio.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#radio-button-state-(type=radio) input type=radio}.
 *
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
final class Radio extends Checkbox
{
    protected array $attributes = ['type' => 'radio'];
}
