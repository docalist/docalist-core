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
declare(strict_types=1);

namespace Docalist\Views;

use Docalist\Core\AdminTables;
use Docalist\Forms\Form;
use Docalist\Table\TableInfo;

/**
 * Crée une nouvelle table par recopie d'une table existante.
 *
 * @var AdminTables $this
 * @var string      $tableName  Nom de la table à recopier.
 * @var TableInfo   $tableInfo  Infos sur la table à créer.
 * @var string      $error      Message d'erreur éventuel à afficher.
 */
?>
<div class="wrap">
    <h2><?= sprintf(__('Recopier la table "%s"', 'docalist-core'), $tableName) ?></h2>

    <p class="description">
        <?= __('Indiquez les paramètres de la nouvelle table :', 'docalist-core') ?>
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
        $form->checkbox('nodata')
             ->setLabel(__('Structure uniquement', 'docalist-core'))
             ->setDescription(__('Ne pas recopier les données', 'docalist-core'));
        $form->submit(__('Créer la table', 'docalist-search'));

        $form->bind($tableInfo);
        $form->display();
    ?>
</div>
