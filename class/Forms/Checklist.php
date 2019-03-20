<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Forms;

/**
 * Une liste de cases à cocher.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Checklist extends Radiolist
{
    /**
     * {@inheritdoc}
     */
    const INPUT_TYPE = 'checkbox';

    /**
     * {@inheritdoc}
     */
    const CSS_CLASS = 'checklist';

    /**
     * {@inheritdoc}
     *
     * Une checklist est obligatoirement multivaluée (et indépendemment de ça, elle peut être repeatable).
     * Le nom du contrôle a toujours '[]' à la fin.
     */
    protected function getControlName(): string
    {
        return parent::getControlName() . '[]';
    }

    /**
     * {@inheritdoc}
     */
    protected function isMultivalued(): bool
    {
        return true;
    }
}
