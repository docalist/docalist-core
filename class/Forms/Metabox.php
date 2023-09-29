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
 * Une metabox wordpress.
 *
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
final class Metabox extends Container
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
