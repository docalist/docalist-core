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
 * Un groupe de champs.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#the-fieldset-element The fieldset element}.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Fieldset extends Container
{
    /**
     * {@inheritDoc}
     */
    protected function hasLayout(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function hasDescriptionBlock(): bool
    {
        return false;
    }
}
