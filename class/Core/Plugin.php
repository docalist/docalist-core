<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Core;

use Docalist\Cache\FileCache;
use Docalist\Table\TableManager;
use Docalist\Table\TableInfo;
use Docalist\Http\JsonResponse;
use Closure;
use Exception;

/**
 * Plugin core de Docalist.
 */
class Plugin {

    /**
     * La configuration du plugin.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Liste des services.
     *
     * @var object[]
     */
    protected $services = array();

    /**
     * Ajoute un service dans le container.
     *
     * @param string $id identifiant unique de l'objet.
     * @param mixed $service le service à ajouter. Cela peut être un scalaire (un
     * paramètre de configuration, par exemple), un objet (par exemple un plugin)
     * ou une closure qui sera invoquée lors du premier appel.
     *
     * @throws Exception S'il existe déjà un service avec l'identifiant indiqué.
     *
     * @return self
     */
    public function add($id, $service) {
        if (isset($this->services[$id])) {
            $message = __('%s existe déjà.', 'docalist-core');
            throw new Exception(sprintf($message, $id));
        }

        $this->services[$id] = $service;

        return $this;
    }

    /**
     * Indique si le container contient un service avec l'identifiant indiqué.
     *
     * @param unknown $id l'identifiant du service recherché.
     *
     * @return bool
     */
    public function has($id) {
        return isset($this->services[$id]);
    }

    /**
     * Retourne le service ayant l'identifiant indiqué.
     *
     * @param string $id l'identifiant de l'objet à retourner
     *
     * @throws Exception Si l'identifiant indiqué n'existe pas.
     *
     * @return mixed
     */
    public function get($id) {
        if (! isset($this->services[$id])) {
            $message = __('%s non trouvé.', 'docalist-core');
            throw new Exception(sprintf($message, $id));
        }

        $service = $this->services[$id];
        if ($service instanceof Closure) {
            return $this->services[$id] = $service($this);
        }

        return $service;
    }

    /**
     * {@inheritdoc}
     */
    public function __construct() {
        // Charge les fichiers de traduction du plugin
        load_plugin_textdomain('docalist-core', false, 'docalist-core/languages');

        // Charge la configuration du plugin
        $this->settings = new Settings('docalist-core');

        // WordPress n'offre aucun moyen simple d'obtenir la racine du site :
        // - ABSPATH ne fonctionne pas si wp est dans un sous-répertoire
        // - get_home_path() ne fonctionne que dans le back-office
        // Pour y remédier on définit le service à la demande "site-root"
        // qui retourne le path absolu du site avec un slash final.
        $this->add('site-root', function() {
            $root = substr($_SERVER['SCRIPT_FILENAME'], 0, -strlen($_SERVER['PHP_SELF']));

            $root = strtr($root, '/\\', DIRECTORY_SEPARATOR);
            $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            return $root;
        });

        // Crée le service "file-cache"
        $this->add('file-cache', function() {
            $dir = get_temp_dir() . 'docalist-cache';
            return new FileCache(docalist('site-root'), $dir);
        });

        // Crée le service "table-manager"
        $this->add('table-manager', function() {
            return new TableManager($this->settings);
        });

        // Enregistre nos propres tables quand c'est nécessaire
        add_action('docalist_register_tables', array($this, 'registerTables'));

        // Crée le service "lookup"
        $this->add('lookup', new Lookup());

        // Définit les lookups de type "table"
        add_filter('docalist_table_lookup', function($value, $source, $search) {
            return $this->get('table-manager')->lookup($source, $search, false);
        }, 10, 3);

        // Définit les lookups de type "thesaurus"
        add_filter('docalist_thesaurus_lookup', function($value, $source, $search) {
            return $this->get('table-manager')->lookup($source, $search, true);
        }, 10, 3);

        // Back office
        add_action('admin_menu', function () {

            // Page "Gestion des tables d'autorité"
            new AdminTables();
        });

        // Gestion des admin notices - à revoir, pas içi
//         add_action('admin_notices', function(){
//             $this->showAdminNotices();
//         });

        // Déclare les JS et les CSS prédéfinis inclus dans docalist-core
        add_action('init', function() { // TODO : wp_enqueue_scripts ne marche pas ?
            $js = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'js' : 'min.js';

            $url = plugins_url('docalist-core');

            wp_register_script(
                'selectize.js',
                "$url/lib/selectize/js/standalone/selectize.$js",
                array('jquery'),
                '0.8.5',
                false // TODO: Passer à true (position top)
            );

            wp_register_style(
                'selectize.css',
                "$url/lib/selectize/css/selectize.default.css",
                false,
                '0.8.5'
            );

            // Todo : handsontable
        });

    }

    /**
     * Affiche les admin-notices qui ont été enregistrés
     * (cf AbstractPlugin::adminNotice).
     */
/*
    protected function showAdminNotices() {
        // Adapté de : http://www.dimgoto.com/non-classe/wordpress-admin_notice/
        if (false === $notices = get_transient(self::ADMIN_NOTICE_TRANSIENT)) {
            return;
        }

        foreach($notices as $notice) {
            list($message, $isError) = $notice;
            printf(
                '<div class="%s"><p>%s</p></div>',
                $isError ? 'error' : 'updated',
                $message
            );
        }

        delete_transient(self::ADMIN_NOTICE_TRANSIENT);
    }
*/
    /**
     * Enregistre les tables prédéfinies.
     *
     * @param TableManager $tableManager
     */
    public function registerTables(TableManager $tableManager) {
        $dir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'tables'  . DIRECTORY_SEPARATOR;

        $tableManager->register(new TableInfo([
            'name' => 'countries',
            'path' => $dir . 'countries.php',
            'label' => __('Table des pays', 'docalist-core'),
            'user' => false,
        ]));

        $tableManager->register(new TableInfo([
            'name' => 'languages',
            'path' => $dir . 'languages.php',
            'label' => __('Table des langues', 'docalist-core'),
            'user' => false,
        ]));
    }
}