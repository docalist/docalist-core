<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Forms;

/**
 * Un widget spécialisé pour la saisie du champ topic de docalist-biblio.
 *
 * Ne devrait pas être là mais pour le moment, la surcharge des vues de
 * formulaires ne marche pas. Il faudrait faire un theme wordpressbiblio qui
 * étend wordpress, mais comme wordpress étend déjà base, on se retrouve avec
 * trois niveaux et actuellement ce n'est pas géré.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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
}
