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

namespace Docalist\Core;

use Docalist\Repository\SettingsRepository;
use Docalist\LogManager;
use Docalist\Views;
use Docalist\Cache\FileCache;
use Docalist\Table\TableManager;
use Docalist\AdminNotices;
use Docalist\Sequences;
use Docalist\Lookup;
use InvalidArgumentException;

// use for migration
use Docalist\Table\TableInfo;

/**
 * Plugin Docalist-Core.
 */
class Plugin {
    /**
     * Initialise le plugin.
     */
    public function __construct() {
        // Charge les fichiers de traduction du plugin
        load_plugin_textdomain('docalist-core', false, 'docalist-core/languages');

        // Définit le path des répertoires docalist (tables, logs, etc.)
        $this->setupPaths();

        // Enregistre les services docalist de base
        $this->setupServices();

        if (isset($_REQUEST['migrate'])) {
            $s = get_option('docalist-core-settings');
            if ($s !== false) {
                $this->migrate($s);
            }
        }

        // Charge la configuration de docalist-core
        docalist('services')->add('docalist-core-settings', function() {
            return new Settings(docalist('settings-repository'));
            // TODO : à supprimer, n'est utilisé que pour les tables personnalisées
        });

        // Définit les actions et les filtres par défaut
        $this->setupHooks();

        // Gestion des admin notices - à revoir, pas içi
//         add_action('admin_notices', function(){
//             $this->showAdminNotices();
//         });
    }

    protected function migrate($option) {
        error_reporting(E_ALL);

        $go = isset($_REQUEST['go']);
        $ok = true;

        header('Content-Type: text/html; charset=UTF8');
        echo "<h1>Les tables perso doivent être migrées.</h1>";
        echo '
            <style>
                .error {font-weight: bold; color: red;}
            </style>
            ';

        echo "<p>", $go ? "**MODE REEL**" : 'mode test', '</p>';

        $tables = json_decode($option);
        if (! is_object($tables)) {
            var_dump($option);
            var_dump($tables);
            die("l'option existe mais json_decode ne retourne pas un objet");
        }
        $tables = $tables->tables;

        $manager = docalist('table-manager'); /* @var $manager TableManager */

        foreach($tables as $table) { /* @var $table TableInfo */
            echo '<h2>', $table->name, '</h2>';
            var_dump($table);

            // Vérifie que l'ancien fichier table existe
            if (!file_exists($table->path)) {
                echo "<p class='error'>La table n'existe pas.</p>";
                $ok = false;
                continue;
            }

            // Vérifie que le nouveau fichier table n'existe pas
            $path = docalist('tables-dir') . DIRECTORY_SEPARATOR . basename($table->path);
            if (file_exists($path)) {
                echo "<p class='error'>La nouvelle table existe déjà.</p>";
                echo "<p>Path de la nouvelle table : <code>$path</code></p>";
                $ok = false;
            }

            // Vérifie qu'il n'existe pas déjà une table avec ce nom
            if ($manager->has($table->name)) {
                echo "<p class='error'>Le manager a déjà une table avec ce nom.</p>";
                $ok = false;
            }

            if (!$ok) {
                continue;
            }

            if ($go) {
                echo "- Copie de la table...";
                if (true !== copy($table->path, $path)) {
                    echo "<p class='error'>Impossible de copier $table->path vers $path.</p>";
                    die();
                }
                echo 'ok<br />';

                // Crée la structure TableInfo de la nouvelle table
                $table->path = $path;
                unset($table->user);
                $table->readonly = false;
                $table = new TableInfo((array)$table);

                echo "- register...";
                $manager->register($table);
                echo 'ok<br />';
            } else {
                echo "<p>OK</p>";
            }
        }

        if ($ok) {
            echo "<h1>Tout à l'air OK. Vous pouvez lancer la migration</h1>";
            echo "<p><a href='?migrate&go'>Go</p>";
        } else {
            echo "<h1>Une ou plusieurs erreurs détectées.</h1>";
        }

        die();
    }

    /**
     * Définit les path docalist par défaut (racine du site, répertoire des
     * données, des logs, des tables, etc.)
     *
     * @return self
     */
    protected function setupPaths() {
        docalist('services')->add([
            // Répertoire racine du site (/)
            'root-dir' => function() {
                return $this->rootDirectory();
            },

            // Répertoire de base (WP_CONTENT_DIR/data)
            'data-dir' => function() {
                return $this->dataDirectory();
            },

            // Répertoire de config (WP_CONTENT_DIR/data/config)
            'config-dir' => function() {
                return $this->dataDirectory('config');
            },

            // Répertoire de cache de docalist (docalist-cache : dans /tmp ou fixé)
            'cache-dir' => function() {
                return $this->cacheDirectory();
            },

            // Répertoire des logs (WP_CONTENT_DIR/data/log)
            'log-dir' => function() {
                return $this->dataDirectory('log');
            },

            // Répertoire des tables (WP_CONTENT_DIR/data/tables)
            'tables-dir' => function() {
                return $this->dataDirectory('tables');
            },
        ]);

        return $this;
    }

    /**
     * Enregistre les services docalist de base (gestionnaire de vues,
     * gestionnaire de cache, gestionnaire de tables, etc.)
     *
     * @return self
     */
    protected function setupServices() {
        // Enregistre les services docalist par défaut
        $services = [

            // Gestion des Settings
            'settings-repository' => function() {
                return new SettingsRepository();
            },

            // Gestion des logs
            'logs' => function() {
                return new LogManager();
            },

            // Gestion des vues
            'views' => function() {
                return new Views();
            },

            // Gestion du cache
            'file-cache' => function() {
                return new FileCache(docalist('root-dir'), docalist('cache-dir'));
            },

            // Gestion des tables
            'table-manager' => function() {
                return new TableManager();
            },

            // Gestion des séquences
            'sequences' => function() {
                return new Sequences();
            },

            // Gestion des lookups
            'lookup' => function() {
                return new Lookup();
            },
        ];

        // Le service admin-notices n'est disponible que dans le back-office
        if (is_admin()) {
            $services['admin-notices'] = new AdminNotices();
        }

        docalist('services')->add($services);

        return $this;
    }

    /**
     * Définit les actions et les filtres par défaut de docalist.
     *
     * @return self
     */
    protected function setupHooks() {
        // Définit les lookups de type "table"
        add_filter('docalist_table_lookup', function($value, $source, $search) {
            return docalist('table-manager')->lookup($source, $search, false);
        }, 10, 3);

        // Définit les lookups de type "thesaurus"
        add_filter('docalist_thesaurus_lookup', function($value, $source, $search) {
            return docalist('table-manager')->lookup($source, $search, true);
        }, 10, 3);

        // Crée l'action ajax "docalist-lookup"
        add_action('wp_ajax_docalist-lookup', $ajaxLookup = function() {
            docalist('lookup')->ajaxLookup();
        });
        add_action('wp_ajax_nopriv_docalist-lookup', $ajaxLookup);

        // Déclare les JS et les CSS inclus dans docalist-core
        add_action('init', function() {
            $this->registerAssets();
        });

        // Crée la page "Gestion des tables d'autorité" dans le back-office
        add_action('admin_menu', function () {
            new AdminTables();
        });

        return $this;
    }

    /**
     * Retourne le path de la racine du site : soit le répertoire de WordPress
     * (si WordPress est installé à la racine de façon classique), soit le
     * répertoire au-dessus (si WordPress est installé dans un sous-répertoire).
     *
     * Remarque : WordPress n'offre aucun moyen simple d'obtenir la racine du
     * site :
     * - ABSPATH ne fonctionne pas si WordPress est dans un sous-répertoire.
     * - get_home_path() ne fonctionne que dans le back-office et n'est pas
     *   disponible en mode cli car SCRIPT_FILENAME n'est pas utilisable.
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    protected function rootDirectory() {
        // Version 1, basée sur SCRIPT_FILENAME : pas dispo en mode cli
        // if (PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server') {
        //     throw new \Exception('root-dir is not available with CLI SAPI');
        // }
        // $root = substr($_SERVER['SCRIPT_FILENAME'], 0, -strlen($_SERVER['PHP_SELF']));

        // $root = strtr($root, '/\\', DIRECTORY_SEPARATOR);
        // $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Version 2, basée sur l'emplacement de wp-config.php
        // Adapté de wordpress/wp-load.php
        $root = rtrim(ABSPATH, '/\\'); // ABSPATH contient un slash final
        if (!file_exists($root . '/wp-config.php') ) {
            $root = dirname($root);
            if (! file_exists($root . '/wp-config.php' ) || file_exists($root . '/wp-settings.php')) {
                throw new InvalidArgumentException('Unable to find root dir');
            }
        }

        return $root;
    }

    /**
     * Retourne le path du répertoire "data" de docalist, c'est-à-dire le
     * répertoire qui contient toutes les données docalist (tables, config,
     * logs, user-data, etc.)
     *
     * Par défaut, il s'agit du répertoire "docalist-data" situé dans le
     * répertoire uploads de WordPress.
     *
     * Si un sous-répertoire est fourni en paramètre, la fonction crée le
     * répertoire s'il n'existe pas déjà et retourne son path absolu.
     *
     * Les répertoires créés par cette fonction sont protégés avec un fichier
     * index.php et un fichier .htaccess.
     *
     * @param string $subdir Optionnel, sous-répertoire.
     *
     * @return string Le path absolu du répertoire demandé.
     */
    public function dataDirectory($subdir = null) {
     // $directory = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'docalist-data';
        $directory = wp_upload_dir();
        $directory = $directory['basedir'];
        $directory = strtr($directory, '/\\', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
        $directory .= DIRECTORY_SEPARATOR . 'docalist-data';

        ! is_dir($directory) && $this->createProtectedDirectory($directory);

        if ($subdir) {
            if (!ctype_alpha($subdir)) {
                throw new InvalidArgumentException("Bad data directory name: '$subdir'");
            }
            $directory .= DIRECTORY_SEPARATOR . $subdir;
            ! is_dir($directory) && $this->createProtectedDirectory($directory);
        }

        return $directory;
    }

    /**
     * Retourne le path du répertoire "cache" de docalist.
     *
     * Par défaut, il s'agit du répertoire "docalist-data" situé dans le
     * répertoire temporaire de WordPress.
     *
     * Le répertoires cache créé est protégé avec un fichier index.php et un
     * fichier .htaccess (au cas où celui-ci se trouve dasn l'arborescence
     * publique du site).
     *
     * @return string
     */
    protected function cacheDirectory() {
        // Par défaut on prend le path indiqué dans wp-config
        if (defined('DOCALIST_CACHE_DIR')) {
            $directory = DOCALIST_CACHE_DIR;
        }

        // Sinon, on utilise le répertoire temporaire du système
        else {
            // Le cache docalist ne doit PAS être partagé entre plusieurs sites
            // (cf. https://github.com/daniel-menard/prisme/issues/302)
            // Pour éviter ça, on inclut l'adresse du site dans le path du cache.
            $site = get_home_url();
            $site = substr($site, strpos($site, '://') + 3);
            $site = rtrim($site, '/');

            $directory = get_temp_dir() . 'docalist-cache/' . $site; // get_temp_dir : slash à la fin
        }

        $directory = strtr($directory, '/\\', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
        ! is_dir($directory) && $this->createProtectedDirectory($directory);

        return $directory;
    }

    /**
     * Crée un répertoire "protégé".
     *
     * Crée le répertoire demandé puis appelle protectDirectory().
     *
     * Important : vous devez vous assurer que le répertoire n'exite pas avant
     * d'appeler cette fonction.
     *
     * @param string $directory Le path absolu du répertoire à créer.
     *
     * @throws InvalidArgumentException Si le répertoire ne peut pas être créé.
     *
     * @return self
     */
    public function createProtectedDirectory($directory) {
        if (! @mkdir($directory, 0700, true)) {
            throw new InvalidArgumentException('Unable to create ' . basename($directory) . ' directory');
        }

        $path = $directory . '/index.php';
        file_put_contents($path, '<?php // Silence is golden.');

        $path = $directory . '/.htaccess';
        file_put_contents($path, 'Deny from all');

        return $this;
    }

    /**
     * Protège un répertoire en créant un fichier index.php ("Silence is
     * golden") et un fichier .htaccess ("Deny From All").
     *
     * Important :
     * - vous devez vous assurer que le répertoire à protéger existe avant
     *   d'appeler cette fonction.
     * - si les fichiers index.php et .htaccess existent déjà, ils sont écrasés.
     *
     * @param string $directory Le path absolu du répertoire à protéger.
     *
     * @return self
     */
    public function protectDirectory($directory) {
        $path = $directory . '/index.php';
        file_put_contents($path, '<?php // Silence is golden.');

        $path = $directory . '/.htaccess';
        file_put_contents($path, 'Deny from all');

        return $this;
    }

    /**
     * Déclare les scripts et styles standard de docalist-core.
     *
     * @return self
     */
    protected function registerAssets() {
        $js = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'js' : 'min.js';

        $url = plugins_url('docalist-core');

        // Bootstrap
        wp_register_style(
            'bootstrap',
            '//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.0/css/bootstrap-combined.min.css',
            [],
            '2.3.0'
        );

        // Selectize
        wp_register_script(
            'selectize',
            "$url/lib/selectize/js/standalone/selectize.$js",
            ['jquery'],
            '0.8.5',
            false // TODO: Passer à true (position top)
        );

        wp_register_style(
            'selectize',
            "$url/lib/selectize/css/selectize.default.css",
            [],
            '0.8.5'
        );

        // Todo : handsontable

        // docalist-forms
        wp_register_script(
            'docalist-forms',
            "$url/views/forms/docalist-forms.js", // TODO: version min.js
            ['jquery','jquery-ui-sortable','selectize'],
            '150527',
            false // TODO: Passer à true (position top)
        );

        // Thème par défaut des formulaires
        wp_register_style(
            'docalist-forms-default',
            "$url/views/forms/default/default.css",
            ['wp-admin'],
            '140318'
        );

        // Thème bootstrap des formulaires
        wp_register_style(
            'docalist-forms-bootstrap',
            "$url/views/forms/bootstrap/bootstrap-theme.css",
            ['bootstrap'],
            '140318'
        );

        // Thème wordpress des formulaires
        wp_register_style(
            'docalist-forms-wordpress',
            "$url/views/forms/wordpress/wordpress-theme.css",
            ['wp-admin'],
            '140927'
        );

        return $this;
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
}