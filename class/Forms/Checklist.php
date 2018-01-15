<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Forms;

/**
 * Une liste de cases à cocher.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Checklist extends Radiolist
{
    const INPUT_TYPE = 'checkbox';

    /**
     * {@inheritdoc}
     *
     * Une checklist est obligatoirement multivaluée (et indépendemment de ça, elle peut être repeatable).
     * Le nom du contrôle a toujours '[]' à la fin.
     */
    protected function getControlName()
    {
        return parent::getControlName() . '[]';
    }

    protected function isMultivalued()
    {
        return true;
    }
}
