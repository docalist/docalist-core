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
 * classées en catégories : des auteurs, par exemple peutvent être classés en
 * fonction de leur rôle (traducteur, illustrateur...), des numéros peuvent
 * être classés apr type de numéro, etc.
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
     * @param array $options Options de formattage.
     *
     * @return string Retourne un code interne identifiant la catégorie (par
     * exemple pour un auteur, c'est le code de l'étiquette de rôle qui sera
     * retourné).
     */
    public function getCategoryCode(array $options = null);

    /**
     * Retourne le libellé de la catégorie à laquelle appartient cette valeur.
     *
     * @param array $options Options de formattage.
     *
     * @return string Retourne le libellé de la catégorie retournée par
     * {@link getCategoryCode()}. Par exemple pour un auteur, c'est le libellé
     * de l'étiquette de rôle qui sera retourné.
     */
    public function getCategoryLabel(array $options = null);
}
