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
 * Un champ input de type hidden.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#hidden-state-(type=hidden) input type=hidden}.
 *
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
final class Hidden extends Input
{
    protected $attributes = ['type' => 'hidden'];

    /**
     * {@inheritDoc}
     */
    protected function hasLayout(): bool
    {
        return false;
    }
}
