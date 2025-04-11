<?php

use Docalist\Kernel\Kernel;

require_once __DIR__ . '/../vendor/autoload.php';

define('DOCALIST_USE_WP_CACHE', false);

// Boote Docalist
(new Kernel('test', false))->boot();

// // Environnement de test
// $GLOBALS['wp_tests_options'] = array(
//     'active_plugins' => array(
//         'docalist-core/docalist-core.php'
//     ),
// );

// // wordpress-tests doit être dans le include_path de php
// // sinon, modifier le chemin d'accès ci-dessous
// require_once 'wordpress-develop/tests/phpunit/includes/bootstrap.php';

// function apply_filters($hook_name, $value, ...$args)
// {
//     return $value;
// }

// wp_styles() : utilisé dans Theme.php
// wp_scripts() : utilisé dans Theme.php