<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Views;

use Docalist\Core\AdminTables;
use Docalist\Table\TableInfo;
use Docalist\Forms\Form;

/**
 * Modifie les propriétés d'une table d'autorité.
 *
 * @var AdminTables $this
 * @var string      $tableName  Nom de la table à modifier.
 * @var TableInfo   $tableInfo  Infos sur la table.
 * @var string      $error      Message d'erreur éventuel à afficher.
 */
?>
<div class="wrap">
    <h1><?= sprintf(__('%s : propriétés', 'docalist-core'), $tableInfo->label() ?: $tableName) ?></h1>

    <p class="description">
        <?= __('Utilisez le formulaire ci-dessous pour modifier les propriétés de la table.', 'docalist-core') ?>
    </p>

    <?php if ($error) :?>
        <div class="error">
            <p><?= $error ?></p>
        </div>
    <?php endif ?>

    <?php
        $form = new Form();

        $form->input('name')->addClass('regular-text');
        $form->input('label')->addClass('large-text');

        $form->input('type')->setAttribute('disabled')->addClass('regular-text');
        $form->input('format')->setAttribute('disabled')->addClass('regular-text');
        $form->input('path')->setAttribute('disabled')->addClass('large-text');

        $form->submit(__('Enregistrer les modifications', 'docalist-search'));

        $form->bind($tableInfo)->display();
    ?>
</div>
