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
 * Un widget spécialisé pour la saisie du champ topic de docalist-biblio.
 *
 * TODO: Ne devrait pas être là mais pour le moment, la surcharge des vues de
 * formulaires ne marche pas. Il faudrait faire un theme wordpressbiblio qui
 * étend wordpress, mais comme wordpress étend déjà base, on se retrouve avec
 * trois niveaux et actuellement ce n'est pas géré.
 */
class TopicsInput extends Element
{
    protected $table;

    public function __construct($name = null, $table = null)
    {
        parent::__construct($name);
        $this->table = $table;
    }

    public function getTable()
    {
        return $this->table;
    }

    protected function isMultivalued()
    {
        return true;
    }

    public function repeatable($repeatable = null)
    {
        return false;
    }
}
