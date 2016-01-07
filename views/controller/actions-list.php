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
 * @subpackage  Views
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Views;

use Docalist\Controller;

/**
 * Liste les actions disponibles dans un contrôleur.
 *
 * @var Controller  $this
 * @var string      $title Titre de la page
 * @var string[]    $actions Liste des actions à affficher.
 */
?>
<div class="wrap">
    <h1><?= $title ?></h1>

    <?php if (empty($actions)): ?>
        <p><?= __("Aucune action n'est disponible dans ce module.", 'docalist-core') ?></p>
    <?php else: ?>
        <ul class="ul-disc">
        <?php foreach ($actions as $action): ?>
            <li>
                <h2>
                    <a href="<?= $this->url($action) ?>"><?= $action ?></a>
                </h2>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</div>
