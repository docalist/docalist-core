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

use Docalist\Http\HtmlResponse;
use Docalist\Http\JsonResponse;
use Exception;
use InvalidArgumentException;

/**
 * Gestionnaire de lookups.
 *
 * Le gestionnaire de lookups est un service qui permet de faire une recherche sur un jeu de données (une table, un
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
     * Services de lookup disponibles.
     *
     * @var array<string,LookupInterface>
     */
    private array $lookupServices;

    /**
     * Définit le service de lookup qui gère les lookups du type indiqué.
     *
     * @param string          $type          Type de lookup (table, thesaurus, index, search, etc.)
     * @param LookupInterface $lookupService Le service lookup correspondant.
     */
    public function setLookupService(string $type, LookupInterface $lookupService): void
    {
        if (isset($this->lookupServices[$type])) {
            throw new InvalidArgumentException(sprintf('Lookup service "%s" is already registered', $type));
        }
        $this->lookupServices[$type] = $lookupService;
    }

    /**
     * Retourne le service de lookup qui gère les lookups du type indiqué.
     *
     * @param string $type Type de lookup (table, thesaurus, index, search, etc.)
     *
     * @return LookupInterface Le service lookup correspondant.
     *
     * @throws InvalidArgumentException Si aucun service n'a été trouvé.
     */
    public function getLookupService(string $type): LookupInterface
    {
        if (!isset($this->lookupServices[$type])) {
            throw new InvalidArgumentException(sprintf('Lookup service "%s" do not exists', $type));
        }

        return $this->lookupServices[$type];
    }

    /**
     * Effectue un lookup et retourne les suggestions obtenues.
     *
     * @param string $type   Type de lookup à exécuter (table, thesaurus, index, search, etc.)
     * @param string $search Termes à rechercher.
     * @param string $source Pour les lookups multi-sources, source à utiliser (nom de table, nom de champ, etc.)
     *
     * @return array<mixed> La méthode retourne toujours un tableau. Si aucune suggestion n'a été trouvée, elle retourne
     *                      un tableau vide.
     *
     * Chaque entrée du tableau est un objet. Les champs retournés dépendent du type de lookup exécuté.
     *
     * @throws InvalidArgumentException Si aucun service ne gère le type de lookup indiqué ou si une erreur survient.
     */
    public function lookup(string $type, string $search, string $source = ''): array
    {
        // Récupère le service qui gère les lookups du type indiqué
        $lookup = $this->getLookupService($type);

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
    public function ajaxLookup(): void
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
    protected function doAjaxLookup(): void
    {
        // Récupère le type de lookup à exécuter
        if (empty($_GET['type'])) {
            throw new InvalidArgumentException('type is required');
        }
        $type = $_GET['type'];
        assert(is_string($type));

        // Récupère la source de données (optionnel)
        $source = $_GET['source'] ?? '';
        assert(is_string($source));

        // Récupère la chaine recherchée (optionnel)
        $search = $_GET['search'] ?? '';
        assert(is_string($search));

        // Exécute le lookup
        ob_start();
        $result = $this->lookup($type, $search, $source);
        $garbage = ob_get_clean();

        // Crée une réponse JSON contenant les résultats
        $json = new JsonResponse($result, 200, [], isset($_GET['pretty'])); // Pour le debug, ajouter "&pretty" dans l'url

        // Détermine la durée de mise en cache des résultats
        $maxAge = $this->getLookupService($type)->getCacheMaxAge();

        // Par défaut, WordPress désactive la mise en cache et ajoute des entêtes "no-cache" (cf. admin_ajax.php)
        // Donc si le service nous a retourné 0 (pas de cache), on n'a rien à faire.

        if (!empty($garbage)) {
            $maxAge = 0; // pas de cache
            $response = new HtmlResponse();
            $response->setContent($garbage.'<pre>'.$json->getContent().'</pre>');
            $json = $response;
        }

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
