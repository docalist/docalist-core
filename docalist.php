<?php
/**
 * This file is part of the 'Docalist Core' plugin.
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

// pas de namespace, la fonction docalist() est globale.

use Docalist\Autoloader;
use Docalist\Services;

/**
 * Retourne un service docalist.
 *
 * @param string $service L'identifiant du service à retourner.
 *
 * @return mixed
 */
function docalist($service) {
    static $services = null; /* @var $services Services */

    // Initialise le gestionnaire de services lors du premier appel
    if (is_null($services)) {
        // Initialise l'autoloader
        require_once __DIR__ . '/class/Autoloader.php';
        $autoloader = new Autoloader([
            'Docalist'       => __DIR__ . '/class',
            'Symfony'        => __DIR__ . '/lib/Symfony',
            'Psr\Log'        => __DIR__ . '/lib/psr/log/Psr/Log',
            'Monolog'        => __DIR__ . '/lib/monolog/monolog/src/Monolog',
            'Docalist\Tests' => __DIR__ . '/tests/Docalist'
        ]);

        // Initialise le gestionnaire de services
        $services = new Services([
            'autoloader' => $autoloader
        ]);

        // Le gestionnaire de services est lui-même un service
        $services->add('services', $services);
    }

    // Retourne le service demandé
    return $services->get($service);
}

// Garantit que le gestionnaire de services, l'autoloader, etc. sont chargés
return docalist('services');