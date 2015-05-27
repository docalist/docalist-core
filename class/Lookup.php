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
 * @version     SVN: $Id$
 */

namespace Docalist;

use Docalist\Http\JsonResponse;
use Exception;

/**
 * Gestion des lookups.
 *
 * Ce service définit une méthode (lookup) et une page ajax (docalist-lookup)
 * qui permettent de faire des lookups sur une source de données.
 *
 * Différents types de lookup peuvent être exécutés : sur une table, sur un
 * index, sur une liste en mémoire (exemple liste des types biblio), etc.
 *
 * Chaque type de lookup est identifié par un identifiant unique.
 *
 * Lorsqu'un lookup de ce type est demandé, le service exécute le filtre
 * "docalist_{type}_lookup" et retourne les résultats obtenus.
 */
class Lookup {

    /**
     * Recherche des entrées dans une table d'autorité ou dans un index
     * docalist-search.
     *
     * La méthode lookup retourne toutes les entrées qui commencent par le
     * préfixe indiqué.
     *
     * @param string $source La source de données à utiliser, sous la forme
     * type:nom (par exemple : table:medias, index:author.suggest, etc.)
     *
     * @param string $search Le chaine recherchée.
     *
     * Pour une table d'autorité, la recherche porte par défaut sur les champs
     * "code" et "label" de la table et retourne toutes les entrées dont le
     * libellé ou le code commencent par le préfixe indiqué.
     *
     * Si la chaine de recherche est de la forme [xxx], alors c'est une
     * recherche par code qui sera exécutée et la méthode retourne l'entrée
     * de la table dont le code correspond exactement à la chaine entre
     * crochets.
     *
     * Pour un index docalist-search de type "completion", la recherche porte
     * toujours sur le terme, la recherche par code n'est pas possible et les
     * crochets ouvrants et fermants sont supprimés.
     *
     * @throws Exception
     *
     * @return array La méthode retourne toujours un tableau. Si aucune réponse
     * ne correspond à la chaine recherchée, elle retourne un tableau vide.
     *
     * Chaque entrée du tableau est un objet. Les champs retournés dépendent
     * de la source utilisée.
     *
     */
    public function lookup($source, $search) {
        $type = strtok($source, ':');
        $source = strtok('');

        $tag = "docalist_{$type}_lookup";

        if (! has_filter($tag)) {
            $message = __('Type de lookup non défini : "%s"', 'docalist-core');
            throw new Exception(sprintf($message, $type));
        }

        return apply_filters($tag, [], $source, $search);
    }

    /**
     * Action ajax permettant de faire des lookups.
     *
     * Le point d'entrée est une url de la forme :
     * http://wordpress/wp-admin/admin-ajax.php?action=docalist-lookup
     *
     * Paramètres :
     * - source : source de données à utiliser
     * - search : chaine recherchée.
     */
    public function ajaxLookup() {
        // Récupère la source de données
        if (empty($_REQUEST['source'])) {
            throw new Exception('source is required');
        }
        $source = $_REQUEST['source'];

        // Récupère la chaine recherchée
        $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';

        // Recherche les entrées qui correspondent aux critères indiqués
        $result = $this->lookup($source, $search);

        // Crée la réponse JSON
        $json = new JsonResponse($result);

        // admin_ajax.php génère des entêtes "no-cache" avant qu'on ait la main
        // comme on veut que les requêtes soient mises en cache, on les supprime
        header_remove();

        // Détermine la durée de mise en cache de la requête lookup
        if ('index' === strtok($source, ':')) {
            $maxAge = 10 * MINUTE_IN_SECONDS;
            // index : peut changer à chaque enregistrement de notice (candidat descripteurs, etc.)
        } else {
            $maxAge = 1 * WEEK_IN_SECONDS;
            // table ou thesaurus : n'est pas sensé changer sans arrêt
        }

        // Paramètre la réponse pour que la navigateur la mette en cache
        $json->setProtocolVersion('1.1'); // 1.0 par défaut dans SF
        $json->setPublic()->setMaxAge($maxAge)->setSharedMaxAge($maxAge);
        // TODO: mettre la durée de cache en config

        // Envoie la réponse au navigateur
        $json->send();

        // Termine la requête et empêche WP de générer un exit code (cf. fin de admin-ajax.php)
        exit();
    }
}