<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Type\Interfaces;

use Docalist\Forms\Element;
use Docalist\Schema\Schema;

/**
 * API permettant de formatter un type de données docalist.
 *
 * Tous les types de données docalist peuvent être formattés (pour les afficher, les exporter, etc.) en appellant
 * la méthode {@link getFormattedValue()}.
 *
 * Chaque type dispose d'un ou plusieurs formats d'affichage et d'options qui lui sont propres pour paramétrer
 * finement l'affichage souhaité.
 *
 * Par exemple, pour une date, on pourra choisir le format d'affichage à utiliser (AAAA, AAAA-MM-JJ, etc.) et
 * indiquer sous forme d'option la langue souhaitée pour afficher les mois en toutes lettres.
 *
 * La méthode {@link getAvailableFormats()} permet d'obtenir la liste des formats disponibles pour un type donné
 * et {@link getDefaultFormat()} permet de connaître le nom du format par défaut.
 *
 * Pour permettre à l'utilisateur de paramétrer lui-même un type, on peut utiliser la méthode
 * {@link getFormatSettingsForm()} qui retourne un formulaire contenant toutes les options disponibles.
 *
 * Les données saisies par l'utilisateur dans ce formulaire peuvent être validées en utilisant la méthode
 * {@link validateFormatSettings()}.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Formattable
{
    /**
     * Retourne la liste des formats d'affichage disponibles.
     *
     * @return array Un tableau de la forme "nom du format" => "libellé".
     *
     * Remarque : le tableau retourné peut être vide si le champ n'a qu'un seul format d'affichage.
     */
    public function getAvailableFormats();

    /**
     * Retourne le nom du format d'affichage par défaut.
     *
     * Par défaut, il s'agit du nom du premier format retourné par la méthode {@link getAvailableFormats()},
     * ou null si celle-ci retourne un tableau vide.
     *
     * @return string|null Retourne le nom du format par défaut.
     */
    public function getDefaultFormat();

    /**
     * Retourne un élément de formulaire permettant de saisir et de modifier les paramètres d'affichage du type :
     * libellé à afficher, format d'affichage, etc.
     *
     * @return Element Un élément de formulaire.
     */
    public function getFormatSettingsForm();

    /**
     * Valide les paramètres d'affichage du type.
     *
     * Typiquement, cette méthode est utilisée pour valider les paramètres saisis par l'utilisateur dans le
     * formulaire généré par {@link getFormatSettingsForm()}.
     *
     * Par défaut, la méthode ne fait rien (elle retourne les paramètres inchangés) mais les classes descendantes
     * peuvent surcharger cette méthode pour faire les vérifications nécessaires.
     *
     * @param array $settings Les paramétres à valider.
     *
     * @return array Les paramètres validés.
     */
    public function validateFormatSettings(array $settings);

    /**
     * Formatte le type.
     *
     * @param array|Schema $options Options de formattage.
     *
     * @return string|array Par défaut, la méthode retourne une chaine contenant la valeur formattée selon les
     * options indiquées.
     *
     * Si l'option 'vue éclatée' (option 'explode' du type {@link Collection}) est activée, la méthode retourne
     * un tableau contenant un ou plusieurs éléments de la forme "catégorie" => "éléments de cette catégorie".
     */
    public function getFormattedValue($options = null);
}
