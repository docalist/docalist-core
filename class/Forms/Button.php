<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
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
 * Un bouton.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#the-button-element The button
 * element}.
 */
class Button extends Element
{
    protected $attributes = ['type' => 'button'];

    /**
     * Crée un bouton.
     *
     * @param string $label Optionnel, le libellé du bouton.
     * @param string $name Optionnel, le nom du bouton.
     * @param array $attributes Optionnel, les attributs du bouton.
     * @param Container $parent Optionnel, le containeur parent du bouton.
     */
    public function __construct($label = null, $name = null, array $attributes = null, Container $parent = null)
    {
        parent::__construct($name, $attributes, $parent);
        !is_null($label) && $this->setLabel($label);
    }

    /**
     * Pour un bouton, le libellé est affiché comme titre du bouton.
     *
     * Indique au containeur qu'il ne doit pas générer de bloc label.
     *
     * @return bool
     */
    protected function hasLabelBlock()
    {
        return false;
    }
}
