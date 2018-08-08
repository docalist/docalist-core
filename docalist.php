<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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
function docalist($service)
{
    static $services = null; /** @var Services $services */

    // Initialise le gestionnaire de services lors du premier appel
    if (is_null($services)) {
        // Initialise l'autoloader
        require_once __DIR__ . '/class/Autoloader.php';
        $autoloader = new Autoloader();
        $autoloader->add('Docalist', DOCALIST_CORE_DIR . '/class');
        $autoloader->add('Symfony', DOCALIST_CORE_DIR . '/lib/Symfony');
        $autoloader->add('Psr\Log', DOCALIST_CORE_DIR . '/lib/psr/log/Psr/Log');
        $autoloader->add('Monolog', DOCALIST_CORE_DIR . '/lib/monolog/monolog/src/Monolog');
        $autoloader->add('Docalist\Tests', DOCALIST_CORE_DIR . '/tests/Docalist');

        // Initialise le gestionnaire de services
        $services = new Services([
            'autoloader' => $autoloader,
        ]);

        // Le gestionnaire de services est lui-même un service
        $services->add('services', $services);
    }

    // Retourne le service demandé
    return $services->get($service);
}

// Garantit que le gestionnaire de services, l'autoloader, etc. sont chargés
return docalist('services');
