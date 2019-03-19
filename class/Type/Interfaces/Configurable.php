<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type\Interfaces;

use Docalist\Forms\Container;

/**
 * API permettant de modifier les paramètres d'un type de données docalist.
 *
 * Tous les types de données docalist disposent peuvent être paramétrés. On peut par exemple choisir le libellé
 * à utiliser, la table d'autorité associée, les droits requis pour y avoir accès, etc.
 *
 * La méthode {@link getSettingsForm()} retourne un {@link Element élément de formulaire} permettant de modifier
 * les options disponibles.
 *
 * Les données saisies par l'utilisateur dans ce formulaire peuvent être validées en utilisant la méthode
 * {@link validateSettings()}.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Configurable
{
    /**
     * Retourne un élément de formulaire permettant de saisir et de modifier les paramètres du type : libellé
     * à utiliser, description, droit requis, etc.
     *
     * @return Container Un élément de formulaire.
     */
    public function getSettingsForm(): Container;

    /**
     * Valide les paramètres de base du type.
     *
     * Typiquement, cette méthode est utilisée pour valider les paramètres saisis par l'utilisateur dans le
     * formulaire généré par {@link settingsForm()}.
     *
     * Par défaut, la méthode ne fait rien (elle retourne les paramètres inchangés) mais les classes descendantes
     * peuvent surcharger cette méthode pour faire les vérifications nécessaires.
     *
     * @param array $settings Les paramétres à valider.
     *
     * @return array Les paramètres validés.
     */
    public function validateSettings(array $settings): array;
}
