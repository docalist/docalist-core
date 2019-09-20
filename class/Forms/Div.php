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
 * Un block div.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Div extends Container
{
    /**
     * {@inheritdoc}
     */
    protected function hasLayout(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function hasDescriptionBlock(): bool
    {
        return false;
    }
}
