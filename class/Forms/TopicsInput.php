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
 * Un widget spécialisé pour la saisie du champ topic de docalist-biblio.
 *
 * Ne devrait pas être là mais pour le moment, la surcharge des vues de
 * formulaires ne marche pas. Il faudrait faire un theme wordpressbiblio qui
 * étend wordpress, mais comme wordpress étend déjà base, on se retrouve avec
 * trois niveaux et actuellement ce n'est pas géré.
 *
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
final class TopicsInput extends Element
{
    /**
     * Table d'autorité.
     */
    protected string $table;

    /**
     * Crée le champ de saisie.
     *
     * @param string $name  optionnel, le nom de l'élément
     * @param string $table table d'autorité
     */
    final public function __construct(string $name = '', string $table = '')
    {
        parent::__construct($name);
        $this->table = $table;
    }

    /**
     * Retourne la table d'autorité.
     */
    final public function getTable(): string
    {
        return $this->table;
    }

    /**
     * {@inheritDoc}
     */
    final protected function isMultivalued(): bool
    {
        return true;
    }
}
