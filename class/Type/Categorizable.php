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

/**
 * API permettant de classer les valeurs d'un champ en catégories (vue éclatée).
 *
 * Les valeurs de certains champs, lorsqu'ils sont répétables, peuvent être
 * classées en catégories : des auteurs, par exemple peuvent être classés en
 * fonction de leur rôle (traducteur, illustrateur...), des numéros peuvent
 * être classés par type de numéro, etc.
 *
 * Cette interface permet à un type de données docalist d'indiquer qu'il est
 * catégorisable. Pour cela, deux méthodes doivent être implémentées :
 * - {@link getCategoryCode()} qui retourne le code de la catégorie à laquelle
 *   appartient le type de données
 * - {@link getCategoryLabel()} qui retourne le libellé de cette catégorie.
 *
 * Lorsqu'un champ supporte cette interface, la classe {@link Collection}
 * propose l'option "vue éclatée" dans les paramétres d'affichage et se charge
 * de regrouper les différentes valeurs du champ par catégorie.
 */
interface Categorizable
{
    /**
     * Retourne le code de la catégorie à laquelle appartient cette valeur.
     *
     * @return string Retourne un code interne identifiant la catégorie (par
     * exemple pour un auteur, c'est le code de l'étiquette de rôle qui sera
     * retourné).
     */
    public function getCategoryCode();

    /**
     * Retourne le libellé de la catégorie à laquelle appartient cette valeur.
     *
     * @return string Retourne le libellé de la catégorie retournée par
     * {@link getCategoryCode()}. Par exemple pour un auteur, c'est le libellé
     * de l'étiquette de rôle qui sera retourné.
     */
    public function getCategoryLabel();

    /**
     * Retourne un libellé utilisé pour désigner la catégorie.
     *
     * Ce libellé permet d'indiquer à l'utilisateur comment seront classées les
     * entrées (avec un message du style "Classer les valeurs par <libellé>").
     *
     * Le message retourné doit être au singulier et sans majuscule de début.
     * Par exemple "langue" et non pas "Langue" ou "langues".
     *
     * @return string Le libellé à utiliser pour désigner la catégorie à
     * l'utilisateur.
     */
    public function getCategoryName();
}
