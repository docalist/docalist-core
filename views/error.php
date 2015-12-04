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
namespace DocalistViews;

use Docalist\Controller;

/**
 * Affiche un message d'erreur
 *
 * @var Controller $this
 * @var string $h2 Titre de la page (optionnel).
 * @var string $h3 Titre de la boite (optionnel).
 * @var string $message Message à afficher.
 * @var string $back Url du lien 'annuler' (optionnel)
 */
! isset($h2) && $h2 = __('Erreur', 'docalist-core');
! isset($h3) && $h3 = __('Erreur', 'docalist-core');
$href = isset($back) ? esc_url($back) : 'javascript:history.go(-1)'
?>

<div class="wrap">
    <h1><?= $h2 ?></h1>

    <div class="notice notice-error notice-large">
        <?php if (isset($h3)) :?>
            <h2 class="notice-title"><?= $h3 ?></h2>
        <?php endif ?>

        <?php if (isset($message)) :?>
            <p><?= $message ?></p>
        <?php endif ?>

        <p>
            <a href="<?= $href ?>" class="button-primary">
                <?= __('Ok', 'docalist-core') ?>
            </a>
        </p>
    </div>
</div>