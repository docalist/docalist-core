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
 * Demande une confirmation à l'utilisateur.
 *
 * Si l'utilisateur clique "ok", la requête en cours est relancée avec en plus
 * le paramètre confirm=1.
 *
 * @var Controller  $this
 * @var string      $h2         Titre de la page (optionnel).
 * @var string      $h3         Titre de la boite de confirmation (optionnel).
 * @var string      $message    Message à afficher.
 * @var string      $back       Url du lien 'annuler' (optionnel)
 */

! isset($h2) && $h2 = __('Confirmation', 'docalist-core');
! isset($h3) && $h3 = __('Attention', 'docalist-core');
$back = isset($back) ? esc_url($back) : 'javascript:history.go(-1)';
$href = add_query_arg('confirm', '1');

// classes ".notice-xxx" de wordpress :
// https://core.trac.wordpress.org/ticket/32244
?>
<div class="wrap">
    <h1><?= $h2 ?></h1>

    <div class="notice notice-warning notice-large">
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

            <a href="<?= $back ?>" class="button">
                <?= __('Annuler', 'docalist-core') ?>
            </a>
        </p>
    </div>
</div>
