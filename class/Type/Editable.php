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

use Docalist\Forms\Field;

/**
 * Editable : API permettant de saisir et modifier un type de données docalist.
 *
 * Tous les types de données docalist disposent d'un ou plusieurs éditeurs (des
 * {@link Docalist\Forms\Field formulaires}) qui peuvent être utilisés pour
 * saisir et modifier des valeurs de ce type.
 *
 * La méthode {@link getAvailableEditors()} permet d'obtenir la liste des
 * éditeurs disponibles pour un type donné et {@link getDefaultEditor()} permet
 * de connaître le nom de l'éditeur par défaut.
 *
 * Pour obtenir le formulaire de saisie, il suffit d'appeller la méthode
 * {@link getEditorForm()}.
 *
 * Pour permettre à l'utilisateur de paramétrer lui-même le formulaire de saisie
 * d'un type, on peut utiliser la méthode {@link getEditorSettingsForm()} qui
 * retourne un formulaire contenant toutes les options d'édition disponibles.
 *
 * Les données saisies par l'utilisateur dans ce formulaire peuvent être
 * validées en utilisant la méthode {@link validateEditorSettings()}.
 */
interface Editable
{

    /**
     * Retourne le formulaire permettant de saisir ce champ.
     *
     * @param array $options Options à appliquer à l'éditeur.
     *
     * @return Field Un champ de formulaire ou un ensemble de champs (fragment).
     */
    public function getEditorForm(array $options = null);

    /**
     * Retourne la liste des éditeurs disponibles pour ce type.
     *
     * @return array Un tableau de la forme "nom éditeur" => "libellé".
     *
     * Remarque : le tableau retourné peut être vide si le champ n'a qu'un
     * seul format d'édition.
     */
    public function getAvailableEditors();

    /**
     * Retourne le nom de l'éditeur par défaut.
     *
     * Par défaut, il s'agit du nom du premier éditeur retourné par la méthode
     * {@link getAvailableEditors()}, ou null si celle-ci retourne un tableau
     * vide.
     *
     * @return string|null Retourne le nom de l'éditeur par défaut.
     */
    public function getDefaultEditor();

    /**
     * Retourne un formulaire permettant de saisir et de modifier les paramètres
     * de saisie du type : libellé à afficher, éditeur à utiliser, valeur par
     * défaut, etc.
     *
     * Retourne le formulaire "paramètres de saisie" du champ.
     *
     * @return Field Un champ de formulaire ou un ensemble de champs (fragment).
     */
    public function getEditorSettingsForm();

    /**
     * Valide les paramètres de saisie du type.
     *
     * Typiquement, cette méthode est utilisée pour valider les paramètres
     * saisis par l'utilisateur dans le formulaire généré par la méthode
     * {@link getEditorSettingsForm()}.
     *
     * Par défaut, la méthode ne fait rien (elle retourne les paramètres
     * inchangés) mais les classes descendantes peuvent surcharger cette méthode
     * pour faire les vérifications nécessaires.
     *
     * @param array $settings Les paramétres à valider.
     *
     * @return array Les paramétres validés.
     */
    public function validateEditorSettings(array $settings);
}