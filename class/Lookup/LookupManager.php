<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Lookup;

use Docalist\Lookup\LookupInterface;
use Docalist\Http\JsonResponse;
use InvalidArgumentException;
use Exception;

/**
 * Gestionnaire de lookups.
 *
 * Le gestionnaire de lookups est un service qui permet de faire une recherche sur un jeu de donénes (une table, un
 * index, la liste des utilisateurs WordPress, la liste des pages existantes, etc.) et de retourner une liste de
 * suggestions.
 *
 * Chaque type de lookup est géré par un service spécifique qui doit implémenter l'interface LookupInterface.
 *
 * Le gestionnaire de lookups définit une méthode générique (lookup) et une page ajax (docalist-lookup) qui se
 * contentent d'invoquer le bon service.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class LookupManager
{
    /**
     * Retourne le service de lookup qui gère les lookups du type indiqué.
     *
     * @param string $type Type de lookup (table, thesaurus, index, search, etc.)
     *
     * @return LookupInterface Le service lookup correspondant.
     *
     * @throws InvalidArgumentException Si aucun service n'a été trouvé.
     */
    public function getLookupService($type)
    {
        // Détermine le nom du service ('{$type}-lookup' par convention)
        $name = $type . '-lookup';

        // Récupère le service (génère une exception s'il n'existe pas)
        $service = docalist('services')->get($name);

        // (debug) Vérifie que c'est bien un service de lookups
        if (! $service instanceof LookupInterface) {
            throw new InvalidArgumentException("Service $name is not a lookup service");
        }

        // Ok
        return $service;
    }

    /**
     * Effectue un lookup et retourne les suggestions obtenues.
     *
     * @param string $type Type de lookup à exécuter (table, thesaurus, index, search, etc.)
     *
     * @param string $search Termes à rechercher.
     *
     * @param string $source Pour les lookups multi-sources, source à utiliser (nom de table, nom de champ, etc.)
     *
     * @return array La méthode retourne toujours un tableau. Si aucune suggestion n'a été trouvée, elle retourne
     * un tableau vide.
     *
     * Chaque entrée du tableau est un objet. Les champs retournés dépendent du type de lookup exécuté.
     *
     * @throws InvalidArgumentException Si aucun service ne gère le type de lookup indiqué ou si une erreur survient.
     */
    public function lookup($type, $search, $source = '')
    {
        // Récupère le service qui gère les lookups du type indiqué
        $lookup = $this->getLookupService($type);

        // S'il s'agit d'un lookup multi-sources, la source est obligatoire
        if (empty($source) && $lookup->hasMultipleSources()) {
            throw new InvalidArgumentException("Source is required for lookups of type $type");
        }

        // Exécute le lookup
        return empty($search) ? $lookup->getDefaultSuggestions($source) : $lookup->getSuggestions($search, $source);
    }

    /**
     * Action ajax permettant de faire des lookups.
     *
     * Le point d'entrée est une url de la forme :
     * http://wordpress/wp-admin/admin-ajax.php?action=docalist-lookup&type=table&source=genre&search=art
     *
     * Paramètres :
     * - type : type de lookup à exécuter
     * - source : source de données à utiliser
     * - search : chaine recherchée.
     */
    public function ajaxLookup()
    {
        // Si une erreur survient pendant le traitement de la requête, cela génère une erreur 500 sur le serveur
        // Pour éviter ça, on exécute tout dans un bloc try/catch et on affiche simplement un message.
        try {
            $this->doAjaxLookup();
        } catch (Exception $e) {
            wp_die($e->getMessage());
        }
    }

    /**
     * Exécute la requête ajax.
     *
     * @throws InvalidArgumentException
     */
    protected function doAjaxLookup()
    {
        // Récupère le type de lookup à exécuter
        if (empty($_GET['type'])) {
            throw new InvalidArgumentException('type is required');
        }
        $type = $_GET['type'];

        // Récupère la source de données (optionnel)
        $source = isset($_GET['source']) ? $_GET['source'] : '';

        // Récupère la chaine recherchée (optionnel)
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        // Exécute le lookup
        $result = $this->lookup($type, $search, $source);

        // Crée une réponse JSON contenant les résultats
        $json = new JsonResponse();
        isset($_GET['pretty']) && $json->setPretty(); // Pour le debug, ajouter "&pretty" dans l'url
        $json->setContent($result);

        // Détermine la durée de mise en cache des résultats
        $maxAge = docalist("{$type}-lookup")->getCacheMaxAge();

        // Par défaut, WordPress désactive la mise en cache et ajoute des entêtes "no-cache" (cf. admin_ajax.php)
        // Donc si le service nous a retourné 0 (pas de cache), on n'a rien à faire.

        // Ajoute les entêtes http permettant au navigateur de mettre la réponse ajax en cache
        if ($maxAge > 0) {
            header_remove(); // Supprime les entêtes "no-cache" ajoutés par admin_ajax.php
            $json->setProtocolVersion('1.1'); // 1.0 par défaut dans SF
            $json->setPublic()->setMaxAge($maxAge)->setSharedMaxAge($maxAge);
        }

        // Envoie la réponse au navigateur
        $json->send();

        // Termine la requête et empêche WP de générer un exit code (cf. fin de admin-ajax.php)
        exit(0);
    }
}
