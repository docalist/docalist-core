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
 * Indique à l'utilisateur qu'aucun outil n'est disponible.
 */

/* @var AdminPage $this */  // La page parent
?>
<div class="wrap">
    <h1><?= $this->menuTitle() ?></h1>

    <p>
        <?= __('Aucun outil disponible.', 'docalist-core') ?>
    </p>
</div>
