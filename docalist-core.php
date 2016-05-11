<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Plugin Name: Docalist Core
 * Plugin URI:  http://docalist.org
 * Description: Docalist: socle de base.
 * Version:     0.12.0
 * Author:      Daniel Ménard
 * Author URI:  http://docalist.org/
 * Text Domain: docalist-core
 * Domain Path: /languages
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Core;

// Définit une constante pour indiquer que ce plugin est activé
define('DOCALIST_CORE', __DIR__);

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
add_action('activate_docalist-core/docalist-core.php', function () {
    // la fonction docalist() est forcèment dispo (on a fait un require)
    // notre autoloader est chargé (inclus dans le namespace général Docalist)
    // par contre Plugin n'est pas chargé (plugins_loaded pas encore exécuté)
    (new Installer())->activate();
});

/*
 * Désactivation du plugin.
 */
add_action('deactivate_docalist-core/docalist-core.php', function () {
    // Quand wp nous désactive, notre plugin est chargé
    (new Installer())->deactivate();
});
