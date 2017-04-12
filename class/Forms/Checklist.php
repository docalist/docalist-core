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
 * Une liste de cases à cocher.
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
