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
 * L'éditeur WYSISWYG de WordPress.
 *
 * Référence WordPress :
 * {@link https://codex.wordpress.org/Function_Reference/wp_editor wp_editor()}.
 */
class WPEditor extends Textarea
{
    /**
     * Version simplifiée ou complète de l'éditeur.
     *
     * @var bool
     */
    protected $teeny = false;

    /**
     * Indique si on utilise la version simplifiée de l'éditeur.
     *
     * @return bool true si l'éditeur est en version simplifie, false s'il est en version complète.
     */
    public function getTeeny()
    {
        return $this->teeny;
    }

    /**
     * Définit la version de l'éditeur à utiliser.
     *
     * @param bool $teeny
     *
     * @return self
     */
    public function setTeeny($teeny = true)
    {
        $this->teeny = (bool) $teeny;

        return $this;
    }
}
