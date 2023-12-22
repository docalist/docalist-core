<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * Plugin Name: Docalist Core
 * Plugin URI:  https://docalist.org/
 * Description: Docalist: socle de base.
 * Version:     3.4.0
 * Author:      Daniel Ménard
 * Author URI:  https://docalist.org/
 * Text Domain: docalist-core
 * Domain Path: /languages
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
declare(strict_types=1);

namespace Docalist\Core;

/**
 * Version du plugin.
 */
define('DOCALIST_CORE_VERSION', '3.4.0'); // Garder synchro avec la version indiquée dans l'entête

/**
 * Path absolu du répertoire dans lequel le plugin est installé.
 *
 * Par défaut, on utilise la constante magique __DIR__ qui retourne le path réel du répertoire et résoud les liens
 * symboliques.
 *
 * Si le répertoire du plugin est un lien symbolique, la constante doit être définie manuellement dans le fichier
 * wp_config.php et pointer sur le lien symbolique et non sur le répertoire réel.
 */
!defined('DOCALIST_CORE_DIR') && define('DOCALIST_CORE_DIR', __DIR__);

/**
 * Path absolu du fichier principal du plugin.
 */
define('DOCALIST_CORE', DOCALIST_CORE_DIR.DIRECTORY_SEPARATOR.basename(__FILE__));

/**
 * Url de base du plugin.
 */
!defined('DOCALIST_CORE_URL') && define('DOCALIST_CORE_URL', (string) plugins_url('', DOCALIST_CORE));

/**
 * Indique si le services ObjectCache de Docalist utilise le cache WordPress ou non.
 */
!defined('DOCALIST_USE_WP_CACHE') && define('DOCALIST_USE_WP_CACHE', false);

/**
 * Path du cache pour le service FileCache de docalist (vide ou non définit = auto).
 */
!defined('DOCALIST_CACHE_DIR') && define('DOCALIST_CACHE_DIR', '');

/*
 * Charge le plugin docalist-core en premier (priorité -PHP_INT_MAX).
 */
add_action('plugins_loaded', function () {
    docalist(DocalistCorePlugin::class)->initialize();
}, -PHP_INT_MAX);

/*
 * Activation du plugin.
 */
register_activation_hook(DOCALIST_CORE, function () {
    // la fonction docalist() est forcèment dispo (on a fait un require)
    // notre autoloader est chargé (inclus dans le namespace général Docalist)
    // par contre Plugin n'est pas chargé (plugins_loaded pas encore exécuté)
    (new Installer())->activate();
});

/*
 * Désactivation du plugin.
 */
register_deactivation_hook(DOCALIST_CORE, function () {
    // Quand wp nous désactive, notre plugin est chargé
    (new Installer())->deactivate();
});
