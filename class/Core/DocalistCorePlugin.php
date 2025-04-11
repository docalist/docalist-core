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

namespace Docalist\Core;

use Docalist\AdminNotices;
use Docalist\Container\ContainerInterface;
use Docalist\Lookup\LookupManager;
use Docalist\Table\TableManager;
use Docalist\Tools\ToolsPage;
use Docalist\Type\Collection\MultiFieldCollection;

/**
 * Plugin Docalist-Core.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class DocalistCorePlugin
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * Initialise le plugin.
     */
    public function initialize(): void
    {
        // Charge les fichiers de traduction du plugin
        add_action('init', function (): void {
            load_plugin_textdomain('docalist-core', false, 'docalist-core/languages');
        });

        if (is_admin()) {
            $this->container->get(AdminNotices::class)->initialize();
        }

        // Crée l'action ajax "docalist-lookup"
        $ajaxLookup = function () {
            $this->container->get(LookupManager::class)->ajaxLookup();
        };
        add_action('wp_ajax_docalist-lookup', $ajaxLookup);
        add_action('wp_ajax_nopriv_docalist-lookup', $ajaxLookup);

        // Déclare les JS et les CSS inclus dans docalist-core
        add_action('init', function () {
            $this->registerAssets();
        });

        // Crée la page "Gestion des tables d'autorité" dans le back-office
        add_action('admin_menu', function () {
            new AdminTables($this->container->get(TableManager::class)); // todo: service + initialize
            // Crée la page "Outils Docalist" dans le back-office
            $this->container->get(ToolsPage::class); // todo: ->initialize()
        });

        // Indique aux collections MultiField si l'utilisateur a accès ou non aux éléments "internal*"
        // Par défaut, le filtre est activé (cf. MultiField), on le désactive pour ceux qui ont la
        // capacité "voir les entrées internal" (initialement, seulement les administrateurs, cf. Installer)
        $disable = current_user_can('manage_options') || current_user_can('docalist_collection_view_internal');
        $disable = apply_filters('docalist_collection_view_internal', $disable);  // les plugins peuvent changer ça
        $disable ? MultiFieldCollection::disableInternalFilter() : MultiFieldCollection::enableInternalFilter();

        // Debug - permet de réinstaller les tables par défaut
        if (isset($_GET['docalist-core-reinstall-tables']) && $_GET['docalist-core-reinstall-tables'] === '1') {
            add_action('init', static function () {
                $installer = new Installer();
                echo 'Uninstall docalist-core tables...<br />';
                $installer->deactivate();
                echo 'Reinstall docalist-core tables...<br />';
                $installer->activate();
                echo 'Done.';
                exit;
            });
        }
    }

    // Code qui suit gardé en commentaire pour le moment, sera peut-être utile dans le kernel.

    // /**
    //  * Définit les path docalist par défaut (racine du site, répertoire des
    //  * données, des logs, des tables, etc.).
    //  *
    //  * @return self
    //  */
    // protected function setupPaths(Services $services)
    // {
    //     $services->addParameter([
    //         // Répertoire racine du site (/)
    //         'root-dir' => function () {
    //             return $this->rootDirectory();
    //         },

    //         // Répertoire de base (WP_CONTENT_DIR/data)
    //         'data-dir' => function () {
    //             return $this->dataDirectory();
    //         },

    //         // Répertoire de config (WP_CONTENT_DIR/data/config)
    //         'config-dir' => function () {
    //             return $this->dataDirectory('config');
    //         },

    //         // Répertoire de cache de docalist (docalist-cache : dans /tmp ou fixé)
    //         'cache-dir' => function () {
    //             return $this->cacheDirectory();
    //         },

    //         // Répertoire des logs (WP_CONTENT_DIR/data/log)
    //         'log-dir' => function () {
    //             return $this->dataDirectory('log');
    //         },

    //         // Répertoire des tables (WP_CONTENT_DIR/data/tables)
    //         'tables-dir' => function () {
    //             return $this->dataDirectory('tables');
    //         },
    //     ]);

    //     return $this;
    // }

    // /**
    //  * Retourne le path de la racine du site : soit le répertoire de WordPress
    //  * (si WordPress est installé à la racine de façon classique), soit le
    //  * répertoire au-dessus (si WordPress est installé dans un sous-répertoire).
    //  *
    //  * Remarque : WordPress n'offre aucun moyen simple d'obtenir la racine du
    //  * site :
    //  * - ABSPATH ne fonctionne pas si WordPress est dans un sous-répertoire.
    //  * - get_home_path() ne fonctionne que dans le back-office et n'est pas
    //  *   disponible en mode cli car SCRIPT_FILENAME n'est pas utilisable.
    //  *
    //  * @return string
    //  *
    //  * @throws InvalidArgumentException
    //  */
    // protected function rootDirectory()
    // {
    //     // Adapté de wordpress/wp-load.php
    //     $root = rtrim(ABSPATH, '/\\'); // ABSPATH contient un slash final
    //     if (!file_exists($root.'/wp-config.php')) {
    //         $root = dirname($root);
    //         if (!file_exists($root.'/wp-config.php') || file_exists($root.'/wp-settings.php')) {
    //             throw new InvalidArgumentException('Unable to find root dir');
    //         }
    //     }

    //     return $root;
    // }

    // /**
    //  * Retourne le path du répertoire "data" de docalist, c'est-à-dire le
    //  * répertoire qui contient toutes les données docalist (tables, config,
    //  * logs, user-data, etc.).
    //  *
    //  * Par défaut, il s'agit du répertoire "docalist-data" situé dans le
    //  * répertoire uploads de WordPress.
    //  *
    //  * Si un sous-répertoire est fourni en paramètre, la fonction crée le
    //  * répertoire s'il n'existe pas déjà et retourne son path absolu.
    //  *
    //  * Les répertoires créés par cette fonction sont protégés avec un fichier
    //  * index.php et un fichier .htaccess.
    //  *
    //  * @param string $subdir Optionnel, sous-répertoire.
    //  *
    //  * @return string Le path absolu du répertoire demandé.
    //  */
    // public function dataDirectory($subdir = null)
    // {
    //     $directory = wp_upload_dir();
    //     $directory = $directory['basedir'];
    //     $directory = strtr($directory, '/\\', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
    //     $directory .= DIRECTORY_SEPARATOR.'docalist-data';

    //     !is_dir($directory) && $this->createProtectedDirectory($directory);

    //     if ($subdir) {
    //         if (!ctype_alpha($subdir)) {
    //             throw new InvalidArgumentException("Bad data directory name: '$subdir'");
    //         }
    //         $directory .= DIRECTORY_SEPARATOR.$subdir;
    //         !is_dir($directory) && $this->createProtectedDirectory($directory);
    //     }

    //     return $directory;
    // }

    // /**
    //  * Retourne le path du répertoire "cache" de docalist.
    //  *
    //  * Par défaut, il s'agit du répertoire "docalist-data" situé dans le
    //  * répertoire temporaire de WordPress.
    //  *
    //  * Le répertoires cache créé est protégé avec un fichier index.php et un
    //  * fichier .htaccess (au cas où celui-ci se trouve dasn l'arborescence
    //  * publique du site).
    //  *
    //  * @return string
    //  */
    // protected function cacheDirectory()
    // {
    //     // Par défaut on prend le path indiqué dans wp-config
    //     if (defined('DOCALIST_CACHE_DIR') && !empty(constant('DOCALIST_CACHE_DIR'))) {
    //         $directory = DOCALIST_CACHE_DIR;
    //     }

    //     // Sinon, on utilise le répertoire temporaire du système
    //     else {
    //         // Le cache docalist ne doit PAS être partagé entre plusieurs sites
    //         // (cf. https://github.com/daniel-menard/prisme/issues/302)
    //         // Pour éviter ça, on inclut l'adresse du site dans le path du cache.
    //         $site = get_home_url();
    //         $site = substr($site, strpos($site, '://') + 3);
    //         $site = rtrim($site, '/');

    //         $directory = get_temp_dir().'docalist-cache/'.$site; // get_temp_dir : slash à la fin
    //     }

    //     $directory = strtr($directory, '/\\', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
    //     !is_dir($directory) && $this->createProtectedDirectory($directory);

    //     return $directory;
    // }

    // /**
    //  * Crée un répertoire "protégé".
    //  *
    //  * Crée le répertoire demandé puis appelle protectDirectory().
    //  *
    //  * Important : vous devez vous assurer que le répertoire n'exite pas avant
    //  * d'appeler cette fonction.
    //  *
    //  * @param string $directory Le path absolu du répertoire à créer.
    //  *
    //  * @return self
    //  *
    //  * @throws InvalidArgumentException Si le répertoire ne peut pas être créé.
    //  */
    // public function createProtectedDirectory($directory)
    // {
    //     if (!@mkdir($directory, 0700, true)) {
    //         throw new InvalidArgumentException('Unable to create '.basename($directory).' directory');
    //     }

    //     $path = $directory.'/index.php';
    //     file_put_contents($path, '<?php // Silence is golden.');

    //     $path = $directory.'/.htaccess';
    //     file_put_contents($path, 'Deny from all');

    //     return $this;
    // }

    // /**
    //  * Protège un répertoire en créant un fichier index.php ("Silence is
    //  * golden") et un fichier .htaccess ("Deny From All").
    //  *
    //  * Important :
    //  * - vous devez vous assurer que le répertoire à protéger existe avant
    //  *   d'appeler cette fonction.
    //  * - si les fichiers index.php et .htaccess existent déjà, ils sont écrasés.
    //  *
    //  * @param string $directory Le path absolu du répertoire à protéger.
    //  *
    //  * @return self
    //  */
    // public function protectDirectory($directory)
    // {
    //     $path = $directory.'/index.php';
    //     file_put_contents($path, '<?php // Silence is golden.');

    //     $path = $directory.'/.htaccess';
    //     file_put_contents($path, 'Deny from all');

    //     return $this;
    // }

    /**
     * Déclare les scripts et styles standard de docalist-core.
     */
    protected function registerAssets(): void
    {
        $js = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'js' : 'min.js';

        $url = DOCALIST_CORE_URL;

        // Selectize
        wp_register_script(
            'selectize',
            "$url/lib/selectize/js/standalone/selectize.$js",
            ['jquery'],
            '0.12.6',
            false
        );

        wp_register_style(
            'selectize',
            "$url/lib/selectize/css/selectize.default.css",
            [],
            '0.12.6'
        );

        // docalist-forms
        wp_register_script(
            'docalist-forms',
            "$url/views/forms/docalist-forms.js",
            ['jquery', 'jquery-ui-sortable', 'selectize'],
            '191209',
            true
        );

        // Thème wordpress des formulaires
        wp_register_style(
            'docalist-forms-wordpress',
            "$url/views/forms/wordpress/wordpress-theme.css",
            ['wp-admin'],
            '191108'
        );

        // Auto resize des textarea
        wp_register_script(
            'docalist-textarea-autosize',
            "$url/lib/autosize/autosize.$js",
            [],
            '4.0.2',
            true
        );
    }
}
