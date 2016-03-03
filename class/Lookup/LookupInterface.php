<?php
/**
 * This file is part of the "Docalist Core" plugin.
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
namespace Docalist\Lookup;

/**
 * Interface pour les services de lookups.
 *
 * Cette interface permet d'implémenter un service de lookup qui pourra être manipulé via le gestionnaire de lookups
 * (LookupManager).
 */
interface LookupInterface
{
    /**
     * Indique si le service de lookups gère une ou plusieurs sources de données.
     *
     * Le service TableLookup, par exemple, gère plusieurs sources (les tables disponibles) alors que la classe
     * UserLookup ne gère qu'une seule source de données (la liste des utilisateurs WordPress).
     *
     * Pour un service multi-sources, le gestionnaire de lookups vérifie que le paramètre "source" a été indiqué
     * et génèrera une exception si ce n'est pas le cas.
     *
     * @return bool
     */
    public function hasMultipleSources() ;

    /**
     * Retourne la durée (en secondes) pendant laquelle les suggestions retournées peuvent être mises en cache.
     *
     * Cette méthode est utilisée par les requêtes ajax pour définir la durée de mise en cache des résultats dans le
     * navigateur.
     *
     * @return int Durée de mise en cache, en secondes, ou zéro pour désactiver le cache.
     */
    public function getCacheMaxAge();

    /**
     * Retourne une liste de suggestions par défaut.
     *
     * Typiquement, cette méthode utilisée quand l'utilisateur veut voir les suggestions proposées mais qu'il n'a
     * pas encore tapé quoi que ce soit.
     *
     * Le résultat retourné dépend du service de lookups utilisé : une table retournera les premières entrées, un
     * index retournera les termes les plus fréquents, etc.
     *
     * @param string $source Source des données (pour un service multi-sources uniquement).
     *
     * @return array Retourne un tableau contenant les suggestions obtenues ou un tableau vide si aucune entrée
     * n'est disponible. La structure des éléments du tableau dépend du service de lookup utilisé.
     */
    public function getDefaultSuggestions($source = '');

    /**
     * Retourne une liste de suggestions pour les termes de recherche passés en paramètre.
     *
     * @param string $search Termes recherchés.
     * @param string $source Source des données (pour un service multi-sources uniquement).
     *
     * Pour les services de lookups multi-sources, ce paramètre indique la table/l'index/le thésaurus/le champ dans
     * lequel rechercher.
     *
     * Pour les services de lookups mono-source, ce paramètre n'est pas utilisé.
     *
     * @return array Retourne un tableau contenant les suggestions obtenues ou un tableau vide si aucune entrée n'a
     * été trouvée pour les termes de recherche indiqués. La structure des éléments du tableau dépend du service de
     * lookup utilisé.
     */
    public function getSuggestions($search, $source = '');
}
