<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * Plugin Name: Docalist Core
 * Plugin URI:  http://docalist.org
 * Description: Docalist: socle de base.
 * Version:     0.14.0
 * Author:      Daniel Ménard
 * Author URI:  http://docalist.org/
 * Text Domain: docalist-core
 * Domain Path: /languages
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Core;

/**
 * Version du plugin.
 */
define('DOCALIST_CORE_VERSION', '0.14.0'); // Garder synchro avec la version indiquée dans l'entête

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
define('DOCALIST_CORE', DOCALIST_CORE_DIR . DIRECTORY_SEPARATOR . basename(__FILE__));

/**
 * Url de base du plugin.
 */
define('DOCALIST_CORE_URL', plugins_url('', DOCALIST_CORE));

/**
 * Définit la fonction principale de docalist.
 *
 * On passe par un fichier externe, inclus via un require_once, pour garantir
 * que la fonction n'est définie qu'une seule fois.
 *
 * La raison, c'est que lors de l'activation d'un plugin, WordPress exécute la
 * fonction plugin_sandbox_scrape qui fait des include du fichier plugin et
 * donc peut réinclure un fichier déjà inclus.
 */
require_once __DIR__ . '/docalist.php';

/*
 * Charge le plugin docalist-core en premier (priorité -PHP_INT_MAX).
 */
add_action('plugins_loaded', function () {
    docalist('services')->add('docalist-core', new Plugin());
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
