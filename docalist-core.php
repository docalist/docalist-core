<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Plugin Name: Docalist Core
 * Plugin URI:  http://docalist.org
 * Description: Docalist: socle de base.
 * Version:     0.3
 * Author:      Daniel Ménard
 * Author URI:  http://docalist.org/
 * Text Domain: docalist-core
 * Domain Path: /languages
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

// pas de namespace, la fonction docalist() est globale.

use Docalist\Autoloader;
use Docalist\Services;
use Docalist\Core\Plugin;

/**
 * Retourne un service docalist.
 *
 * @param string $service L'identifiant du service à retourner.
 *
 * @return mixed
 */
function docalist($service) {
    /* @var $services Services */
    static $services = null;

    // Au premier appel, on initialise l'instance
    if (is_null($services)) {
        // Initialise l'autoloader
        require_once __DIR__ . '/class/Autoloader.php';
        $autoloader = new Autoloader([
            'Docalist' => __DIR__ . '/class',
            'Symfony' => __DIR__ . '/lib/Symfony'
        ]);

        // Si on est sous phpunit, ajoute le path des tests
        if (defined('PHPUnit_MAIN_METHOD')) {
            $autoloader->add('Docalist\Tests', __DIR__ . '/tests/Docalist');
        }

        // Initialise le gestionnaire de services
        $services = new Services();

        // Le gestionnaire de services est lui-même un service
        $services->add('services', $services);

        // Ajoute l'autoloader dans la liste des services disponibles
        $services->add('autoloader', $autoloader);
    }

    // Retourne le service demandé
    return $services->get($service);
}

/**
 * Charge les plugins Docalist
 */
add_action('plugins_loaded', function() {
    // Charge le plugin docalist-core
    docalist('services')->add('docalist-core', new Plugin());

    //  Charge tous les autres plugins docalist
    do_action('docalist_loaded');
});