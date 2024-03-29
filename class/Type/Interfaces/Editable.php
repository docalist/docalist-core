<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Type\Interfaces;

use Docalist\Forms\Container;
use Docalist\Forms\Element;
use Docalist\Schema\Schema;

/**
 * API permettant de saisir et modifier un type de données docalist.
 *
 * Tous les types de données docalist disposent d'un ou plusieurs éditeurs (des
 * {@link Docalist\Forms\Element élément de formulaires}) qui peuvent être utilisés pour saisir et modifier des
 * valeurs de ce type.
 *
 * La méthode {@link getAvailableEditors()} permet d'obtenir la liste des éditeurs disponibles pour un type donné
 * et {@link getDefaultEditor()} permet de connaître le nom de l'éditeur par défaut.
 *
 * Pour obtenir le formulaire de saisie, il suffit d'appeller la méthode {@link getEditorForm()}.
 *
 * Pour permettre à l'utilisateur de paramétrer lui-même le formulaire de saisie d'un type, on peut utiliser
 * la méthode {@link getEditorSettingsForm()} qui retourne un formulaire contenant toutes les options d'édition
 * disponibles.
 *
 * Les données saisies par l'utilisateur dans ce formulaire peuvent être validées en utilisant la méthode
 * {@link validateEditorSettings()}.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Editable
{
    /**
     * Retourne la liste des éditeurs disponibles pour ce type.
     *
     * @return array<string,string> Un tableau de la forme "nom éditeur" => "libellé".
     *
     * Remarque : le tableau retourné peut être vide si le champ n'a qu'un seul format d'édition.
     */
    public function getAvailableEditors(): array;

    /**
     * Retourne le nom de l'éditeur par défaut.
     *
     * Par défaut, il s'agit du nom du premier éditeur retourné par la méthode {@link getAvailableEditors()},
     * ou null si celle-ci retourne un tableau vide.
     *
     * @return string Retourne le nom de l'éditeur par défaut.
     */
    public function getDefaultEditor(): string;

    /**
     * Retourne un élément de formulaire permettant de saisir et de modifier les paramètres de saisie du type :
     * libellé à afficher, éditeur à utiliser, valeur par défaut, etc.
     *
     * Retourne le formulaire "paramètres de saisie" du champ.
     *
     * @return Container Un élément de formulaire.
     */
    public function getEditorSettingsForm(): Container;

    /**
     * Valide les paramètres de saisie du type.
     *
     * Typiquement, cette méthode est utilisée pour valider les paramètres saisis par l'utilisateur dans le
     * formulaire généré par la méthode {@link getEditorSettingsForm()}.
     *
     * Par défaut, la méthode ne fait rien (elle retourne les paramètres inchangés) mais les classes descendantes
     * peuvent surcharger cette méthode pour faire les vérifications nécessaires.
     *
     * @param array<mixed> $settings Les paramétres à valider.
     *
     * @return array<mixed> Les paramétres validés.
     */
    public function validateEditorSettings(array $settings): array;

    /**
     * Retourne un élément de formulaire permettant de saisir ce champ.
     *
     * @param array<mixed>|Schema|null $options Options à appliquer à l'éditeur.
     *
     * @return Element Un élément de formulaire.
     */
    public function getEditorForm($options = null): Element;
}
