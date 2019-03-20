<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Type\Interfaces\Categorizable;
use Docalist\Type\Collection\MultiFieldCollection;
use Docalist\Type\TableEntry;

/**
 * Un MultiField est un composite qui permet de regrouper plusieurs champs
 * similaires dans un champ unique.
 *
 * Par exemple, si on a besoin de plusieurs champs "date" (date de début, date
 * de fin, date de parution...) on peut créer un multifield date unique qui
 * contiendra deux sous-champs, "type de date" et "valeur".
 *
 * Le champ "type de date", de type {@link TableEntry} sera associé à une table
 * d'autorité qui indique les différents types de dates possibles et le champ
 * "valeur" contiendra la valeur de la date.
 *
 * Lorsqu'un Multifield est répétable, le type {@link Collection} propose
 * automatiquement l'option "vue éclatée" et des options permettant de filtrer
 * les valeurs à afficher.
 *
 * Cela permet de manipuler le champ (en saisie, en affichage ou en recherche)
 * comme s'il s'agissait d'un champ unique ou au contraire comme s'il s'agissait
 * de champs séparés.
 *
 * Lorsque le champ est affiché en vue éclatée, c'est le libellé qui figure dans
 * la table d'autorité qui est utilisé pour chacune des occurences du champ.
 *
 * Un MultiField est un {@link Composite} donc il peut contenir n'importe quels
 * champs. Par contre, il doit avoir un champ de type {@link TableEntry}
 * utilisable pour classer les différentes entrées et pour permettre au MultiField
 * d'implémenter l'interface {@link Categorizable}.
 *
 * Exemples de champs MultiField (dans docalist-biblio) :
 * - author : classement par role,
 * - corporation : classement par role,
 * - othertitle : classement par type,
 * - translation : classement par language,
 * - date : classement par type,
 * - number : classement par type,
 * - extent : classement par type,
 * - editor : classement par role,
 * - topic : classement par type,
 * - content : classement par type,
 * - link : classement par type,
 * - relation : classement par type.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class MultiField extends Composite implements Categorizable
{
    /**
     * Retourne le sous-champ utilisé pour classer les occurences du champ.
     *
     * @return TableEntry
     */
    abstract protected function getCategoryField(): TableEntry;

    /**
     * {@inheritDoc}
     */
    public function getDefaultEditor(): string
    {
        return 'table';
    }

    /**
     * {@inheritDoc}
     */
    public static function getCollectionClass(): string
    {
        return MultiFieldCollection::class;
    }

    // -------------------------------------------------------------------------
    // Interface Categorizable
    // -------------------------------------------------------------------------

    /**
     * {@inheritDoc}
     */
    public function getCategoryCode(): string
    {
        return $this->getCategoryField()->getPhpValue();
    }

    /**
     * {@inheritDoc}
     */
    public function getCategoryLabel(): string
    {
        return $this->getCategoryField()->getEntryLabel();
    }

    /**
     * {@inheritDoc}
     */
    public function getCategoryName(): string
    {
        if ($schema = $this->getCategoryField()->getSchema()) {
            $name = $schema->label();
            if ($name) {
                return lcfirst($name);
            }
        }

        return __('catégorie', 'docalist-core');
    }
}
