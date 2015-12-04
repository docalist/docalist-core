<?php

/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Type;

use Docalist\Type\Interfaces\Categorizable;

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
 * Le champ à utiliser comme clé de classement peut être indiqué soit en
 * surchargeant la méthode {@link getCategoryField()}, soit dans la propriété
 * 'category-field' du schéma associé au MultiField. Par défaut, il s'agit du
 * champ 'type', donc ce n'est nécessaire que si un champ différent est utilisé.
 *
 * Exemples de champs MultiField (dans docalist-biblio) :
 * - author : classement par role,
 * - organisation : classement par role,
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
 */
class MultiField extends Composite implements Categorizable
{
    /**
     * Retourne le nom du champ utilisé pour classer les occurences du champ.
     *
     * @return string Par défaut, il s'agit du champ 'type'. Les classes descendantes
     * doivent surcharger cette méthode si le nom du champ à utiliser est différent.
     *
     * Remarque : le champ retourné doit exister et doit être de type {@link TableEntry}.
     */
    protected function getCategoryField()
    {
        return 'type';
    }

    // -------------------------------------------------------------------------
    // Interface Categorizable
    // -------------------------------------------------------------------------

    public function getCategoryCode()
    {
        $field = isset($this->schema->value['category-field'])
            ? $this->schema->value['category-field']
            : $this->getCategoryField();

        return $this->$field->value();
    }

    public function getCategoryLabel()
    {
        return $this->__get($this->getCategoryField())->getFormattedValue(['format' => 'label']);
    }

    public function getCategoryName()
    {
        if ($schema = $this->__get($this->getCategoryField())->schema()) {
            $name = $schema->label();
            if ($name) {
                return lcfirst($name);
            }
        }

        return __('catégorie', 'docalist-core');
    }
}