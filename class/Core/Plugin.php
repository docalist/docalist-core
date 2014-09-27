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

use Docalist\Views;
use Docalist\Cache\FileCache;
use Docalist\Table\TableManager;
use Docalist\Table\TableInfo;
use Docalist\Sequences;
use Docalist\Repository\SettingsRepository;

/**
 * Plugin core de Docalist.
 */
class Plugin {
    /**
     * {@inheritdoc}
     */
    public function __construct() {
        // Charge les fichiers de traduction du plugin
        load_plugin_textdomain('docalist-core', false, 'docalist-core/languages');

        // Enregistre les services docalist par défaut
        docalist('services')->add([
            // Service settings-repository
            'settings-repository' => function() {
                return new SettingsRepository();
            },

            // Configuration de docalist-core
            'docalist-core-settings' => function($services) {
                return new Settings($services->get('settings-repository'));
            },

            // site-root
            // WordPress n'offre aucun moyen simple d'obtenir la racine du site :
            // - ABSPATH ne fonctionne pas si wp est dans un sous-répertoire
            // - get_home_path() ne fonctionne que dans le back-office
            // Pour y remédier on définit le service à la demande "site-root"
            // qui retourne le path absolu du site avec un slash final.
            'site-root' => function() {
                if (PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server') {
                    throw new \Exception('site-root is not avalable with CLI SAPI');
                }
                $root = substr($_SERVER['SCRIPT_FILENAME'], 0, -strlen($_SERVER['PHP_SELF']));

                $root = strtr($root, '/\\', DIRECTORY_SEPARATOR);
                $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

                return $root;
            },

            // Service "views"
            'views' => function() {
                return new Views();
            },

            // Service "file-cache"
            'file-cache' => function($services) {
                $dir = get_temp_dir() . 'docalist-cache';
                return new FileCache($services->get('site-root'), $dir);
            },

            // Service "table-manager"
            'table-manager' => function($services) {
                return new TableManager($services->get('docalist-core-settings'));
            },

            // Crée le service "sequences"
            'sequences' =>function() {
                return new Sequences();
            },

            // Service "lookup"
            'lookup' => new Lookup(),
        ]);

        // Enregistre nos propres tables quand c'est nécessaire
        add_action('docalist_register_tables', array($this, 'registerTables'));

        // Définit les lookups de type "table"
        add_filter('docalist_table_lookup', function($value, $source, $search) {
            return docalist('table-manager')->lookup($source, $search, false);
        }, 10, 3);

        // Définit les lookups de type "thesaurus"
        add_filter('docalist_thesaurus_lookup', function($value, $source, $search) {
            return docalist('table-manager')->lookup($source, $search, true);
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
        add_action('init', function() {
            $this->registerAssets();
        });
    }

    /**
     * Déclare les scripts et styles standard de docalist-core.
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
            '140927',
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

        // Tables des langues complète
        $tableManager->register(new TableInfo([
            'name' => 'ISO-639-2_alpha3_fr',
            'path' => $dir . 'languages/ISO-639-2_alpha3_fr.txt',
            'label' => __('Liste complète des codes langues 3 lettres en français (ISO-639-2)', 'docalist-core'),
            'format' => 'table',
            'type' => 'languages',
            'user' => false,
        ]));

        $tableManager->register(new TableInfo([
            'name' => 'ISO-639-2_alpha3_en',
            'path' => $dir . 'languages/ISO-639-2_alpha3_en.txt',
            'label' => __('Liste complète des codes langues 3 lettres en anglais (ISO-639-2)', 'docalist-core'),
            'format' => 'table',
            'type' => 'languages',
            'user' => false,
        ]));

        // Tables des langues simplifiées (langues officielles de l'union européenne)
        $tableManager->register(new TableInfo([
            'name' => 'ISO-639-2_alpha3_EU_fr',
            'path' => $dir . 'languages/ISO-639-2_alpha3_EU_fr.txt',
            'label' => __('Codes 3 lettres en français des langues officielles de l\'Union Européenne (ISO-639-2)', 'docalist-core'),
            'format' => 'table',
            'type' => 'languages',
            'user' => false,
        ]));

        $tableManager->register(new TableInfo([
            'name' => 'ISO-639-2_alpha3_EU_en',
            'path' => $dir . 'languages/ISO-639-2_alpha3_EU_en.txt',
            'label' => __('Codes 3 lettres en anglais des langues officielles de l\'Union Européenne (ISO-639-2)', 'docalist-core'),
            'format' => 'table',
            'type' => 'languages',
            'user' => false,
        ]));

        // Tables de conversion des codes langues
        $tableManager->register(new TableInfo([
            'name' => 'ISO-639-2_alpha2-to-alpha3',
            'path' => $dir . 'languages/ISO-639-2_alpha2-to-alpha3.txt',
            'label' => __('Table de conversion "alpha2 -> alpha3" pour les codes langues (ISO-639-2)', 'docalist-core'),
            'format' => 'conversion',
            'type' => 'languages',
            'user' => false,
        ]));

        // Tables des pays
        $tableManager->register(new TableInfo([
            'name' => 'ISO-3166-1_alpha2_fr',
            'path' => $dir . 'countries/ISO-3166-1_alpha2_fr.txt',
            'label' => __('Codes pays 2 lettres en français (ISO-3166-1)', 'docalist-core'),
            'format' => 'table',
            'type' => 'countries',
            'user' => false,
        ]));

        $tableManager->register(new TableInfo([
            'name' => 'ISO-3166-1_alpha2_EN',
            'path' => $dir . 'countries/ISO-3166-1_alpha2_en.txt',
            'label' => __('Codes pays 2 lettres en anglais (ISO-3166-1)', 'docalist-core'),
            'format' => 'table',
            'type' => 'countries',
            'user' => false,
        ]));

        $tableManager->register(new TableInfo([
            'name' => 'ISO-3166-1_alpha3-to-alpha2',
            'path' => $dir . 'countries/ISO-3166-1_alpha3-to-alpha2.txt',
            'label' => __('Table de conversion "alpha3 -> alpha2" pour les codes pays (ISO-3166-1)', 'docalist-core'),
            'format' => 'conversion',
            'type' => 'countries',
            'user' => false,
        ]));
    }
}