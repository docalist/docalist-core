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

use Docalist\Table\TableManager;
use Docalist\Table\TableInfo;
/**
 * Installation/désinstallation de docalist-core.
 */
class Installer {

    /**
     * Initialise l'installateur.
     */
    public function __construct() {
        // Charge le plugin docalist-core si ce n'est pas encore fait : quand wp
        // exécute "plugin_sandbox_scrape", plugins_loaded n'a pas encore été appellé
        if (! docalist('services')->has('docalist-core')) {
            docalist('services')->add('docalist-core', new Plugin());
        }
    }

    /**
     * Activation : enregistre les tables prédéfinies.
     *
     */
    public function activate() {
        $tableManager = docalist('table-manager'); /* @var $tableManager TableManager */

        // Enregistre les tables prédéfinies
        foreach($this->tables() as $name => $table) {
            $table['name'] = $name;
            $table['lastupdate'] = date_i18n('Y-m-d H:i:s', filemtime($table['path']));
            $tableManager->register(new TableInfo($table));
        }
    }

    /**
     * Désactivation : supprime les tables prédéfinies.
     */
    public function deactivate() {
        $tableManager = docalist('table-manager'); /* @var $tableManager TableManager */

        // Supprime les tables prédéfinies
        foreach(array_keys($this->tables()) as $table) {
            $tableManager->unregister($table);
        }
    }


    /**
     * Retourne la liste des tables prédéfinies.
     *
     * @return array
     */
    protected function tables() {
        $dir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'tables'  . DIRECTORY_SEPARATOR;
        return [
            // Tables des langues complète
            'ISO-639-2_alpha3_fr' => [
                'path' => $dir . 'languages/ISO-639-2_alpha3_fr.txt',
                'label' => __('Liste complète des codes langues 3 lettres en français (ISO-639-2)', 'docalist-core'),
                'format' => 'table',
                'type' => 'languages',
                'creation' => '2014-03-14 10:11:23',
            ],
            'ISO-639-2_alpha3_en' => [
                'path' => $dir . 'languages/ISO-639-2_alpha3_en.txt',
                'label' => __('Liste complète des codes langues 3 lettres en anglais (ISO-639-2)', 'docalist-core'),
                'format' => 'table',
                'type' => 'languages',
                'creation' => '2014-03-14 10:11:43',
            ],

            // Tables des langues simplifiées (langues officielles de l'union européenne)
            'ISO-639-2_alpha3_EU_fr' => [
                'path' => $dir . 'languages/ISO-639-2_alpha3_EU_fr.txt',
                'label' => __('Codes 3 lettres en français des langues officielles de l\'Union Européenne (ISO-639-2)', 'docalist-core'),
                'format' => 'table',
                'type' => 'languages',
                'creation' => '2014-03-15 09:01:39',
            ],
            'ISO-639-2_alpha3_EU_en' => [
                'path' => $dir . 'languages/ISO-639-2_alpha3_EU_en.txt',
                'label' => __('Codes 3 lettres en anglais des langues officielles de l\'Union Européenne (ISO-639-2)', 'docalist-core'),
                'format' => 'table',
                'type' => 'languages',
                'creation' => '2014-03-15 09:01:39',
            ],

            // Tables de conversion des codes langues
            'ISO-639-2_alpha2-to-alpha3' => [
                'path' => $dir . 'languages/ISO-639-2_alpha2-to-alpha3.txt',
                'label' => __('Table de conversion "alpha2 -> alpha3" pour les codes langues (ISO-639-2)', 'docalist-core'),
                'format' => 'conversion',
                'type' => 'languages',
                'creation' => '2014-03-14 10:12:15',
            ],

            // Tables des pays
            'ISO-3166-1_alpha2_fr' => [
                'path' => $dir . 'countries/ISO-3166-1_alpha2_fr.txt',
                'label' => __('Codes pays 2 lettres en français (ISO-3166-1)', 'docalist-core'),
                'format' => 'table',
                'type' => 'countries',
                'creation' => '2014-03-14 10:08:17',
            ],
            'ISO-3166-1_alpha2_EN' => [
                'path' => $dir . 'countries/ISO-3166-1_alpha2_en.txt',
                'label' => __('Codes pays 2 lettres en anglais (ISO-3166-1)', 'docalist-core'),
                'format' => 'table',
                'type' => 'countries',
                'creation' => '2014-03-14 10:08:32',
            ],
            'ISO-3166-1_alpha3-to-alpha2' => [
                'path' => $dir . 'countries/ISO-3166-1_alpha3-to-alpha2.txt',
                'label' => __('Table de conversion "alpha3 -> alpha2" pour les codes pays (ISO-3166-1)', 'docalist-core'),
                'format' => 'conversion',
                'type' => 'countries',
                'creation' => '2014-03-14 10:09:01',
            ],
        ];
    }
}