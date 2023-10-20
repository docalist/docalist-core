<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */

// pas de namespace, la fonction docalist() est globale.

declare(strict_types=1);

use Docalist\Autoloader;
use Docalist\Services;

/**
 * Retourne un service docalist.
 *
 * @param string $service L'identifiant du service à retourner
 */
function docalist(string $service): mixed
{
    /** @var ?Services $services */
    static $services = null;

    // Initialise le gestionnaire de services lors du premier appel
    if (is_null($services)) {
        // Initialise l'autoloader
        require_once __DIR__.'/class/Autoloader.php';
        $autoloader = new Autoloader();
        $autoloader->add('Docalist', __DIR__.'/class');
        $autoloader->add('Docalist\Tests', __DIR__.'/tests/Docalist');

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
