<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tools\Views;

use Docalist\AdminPage;

/**
 * Affiche une "carte" présentant les outils Docalist dans la page WordPress "Outils disponibles".
 */

/* @var AdminPage $this */  // La page parent
?>
<div class="card">
    <h2 class="title"><?= $this->menuTitle() ?></h2>
    <p>
        <?= __(
            'Permet de lancer des scripts et des traitements longs liés à Docalist
            (migration de données, corrections, opérations de maintenance...)',
            'docalist-core'
        ); ?>

        <a href="<?= esc_attr($this->getUrl()) ?>">
            <?= __('Voir la liste des outils Docalist.', 'docalist-core') ?>
        </a>
    </p>
</div>
