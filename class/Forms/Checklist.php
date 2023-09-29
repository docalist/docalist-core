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
 * Une liste de cases à cocher.
 *
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
class Checklist extends Radiolist
{
    /**
     * {@inheritdoc}
     */
    public const INPUT_TYPE = 'checkbox';

    /**
     * {@inheritdoc}
     */
    public const CSS_CLASS = 'checklist';

    /**
     * {@inheritdoc}
     *
     * Une checklist est obligatoirement multivaluée (et indépendemment de ça, elle peut être repeatable).
     * Le nom du contrôle a toujours '[]' à la fin.
     */
    protected function getControlName(): string
    {
        return parent::getControlName().'[]';
    }

    /**
     * {@inheritdoc}
     */
    protected function isMultivalued(): bool
    {
        return true;
    }
}
