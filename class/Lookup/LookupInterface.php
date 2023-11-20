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

namespace Docalist\Lookup;

/**
 * Interface pour les services de lookups.
 *
 * Cette interface permet d'implémenter un service de lookup qui pourra être manipulé via le gestionnaire de lookups
 * (LookupManager).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface LookupInterface
{
    /**
     * Retourne la durée (en secondes) pendant laquelle les suggestions retournées peuvent être mises en cache.
     *
     * Cette méthode est utilisée par les requêtes ajax pour définir la durée de mise en cache des résultats dans le
     * navigateur.
     *
     * @return int Durée de mise en cache, en secondes, ou zéro pour désactiver le cache.
     */
    public function getCacheMaxAge(): int;

    /**
     * Retourne une liste de suggestions par défaut.
     *
     * Typiquement, cette méthode est utilisée quand l'utilisateur veut voir les suggestions proposées mais qu'il
     * n'a pas encore tapé quoi que ce soit.
     *
     * Le résultat retourné dépend du service de lookups utilisé : une table retournera les premières entrées, un
     * index retournera les termes les plus fréquents, etc.
     *
     * @param string $source Source des données (pour un service multi-sources uniquement).
     *
     * @return array<mixed> Retourne un tableau contenant les suggestions obtenues ou un tableau vide si aucune entrée
     * n'est disponible. La structure des éléments du tableau dépend du service de lookup utilisé.
     */
    public function getDefaultSuggestions(string $source = ''): array;

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
     * @return array<mixed> Retourne un tableau contenant les suggestions obtenues ou un tableau vide si aucune entrée
     * n'a été trouvée pour les termes de recherche indiqués. La structure des éléments du tableau dépend du service
     * de lookup utilisé.
     */
    public function getSuggestions(string $search, string $source = ''): array;

    /**
     * Détermine le libellé à afficher pour la liste de codes passés en paramètres.
     *
     * Cette méthode est utilisée pour réafficher des données qui ont été saisies via un système de lookups.
     *
     * Typiquement, les données à convertir sont des codes (par exemple issus d'une table) et la conversion va
     * consister à indiquer le libellé de chacun des codes.
     *
     * @param array<int,string>  $data   Un tableau contenant les données à convertir (par exemple ['FR', 'DE']).
     * @param string $source La source à utiliser (pour un service multi-sources uniquement).
     *
     * @return array<string,string> Les données converties (par exemple ['FR' => 'France', 'DE' => 'Allemagne']).
     */
    public function convertCodes(array $data, string $source = ''): array;
}
