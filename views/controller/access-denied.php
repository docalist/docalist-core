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
namespace Docalist\Views;

use Docalist\Controller;

/**
 * Génère une page "Accès non autorisé".
 *
 * @var Controller  $this
 * @var string      $action Le nom de l'action exécutée.
 */

$title = __('Accès refusé', 'docalist-core');
$message = __("Désolé, mais vous n'avez pas les droits requis pour exécuter cette action.", 'docalist-core');
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= $title ?></title>
    </head>
    <body>
        <h1><?= $title ?></h1>
        <p><?= $message ?></p>
    </body>
</html>
