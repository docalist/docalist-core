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
 * @version     $Id$
 */
namespace Docalist\Views;
use Docalist\Table\TableInfo;

/**
 * Liste des tables d'autorité.
 *
 * @param TableInfo[] $tables Liste des tables.
 */
?>
<div class="wrap">
    <?= screen_icon() ?>
    <h2><?= __("Gestion des tables d'autorité", 'docalist-core') ?></h2>

    <p class="description"><?= __("
        Cette page vous permet de gérer les tables d'autorité Docalist.

        Il existe deux types de tables : les tables personnalisées (celles que vous
        créez) et les tables prédéfinies (gérées par les plugins).

        Pour créer une nouvelle table personnalisée, faites une copie d'une table
        existante.

        Vous pouvez modifier comme vous le souhaitez les propriétés et le contenu
        des tables personnalisées mais vous ne pouvez pas modifier les tables
        prédéfinies (vous pouvez seulement afficher leur contenu et les copier).
    ", 'docalist-core') ?>
    </p>

    <?php
    /**
     * On génère deux tableaux widefat : un avec toutes les tables
     * personnalisées, un autre avec les tables prédéfinies.
     */
    $types = [
        __("Tables personnalisées", 'docalist-core') => false,
        __("Tables prédéfinies", 'docalist-core') => true,
    ];

    foreach($types as $title => $readonly): ?>

        <h3><?= $title ?></h3>

        <table class="widefat fixed">

        <thead>
            <tr>
                <th><?= __('Nom', 'docalist-core') ?></th>
                <th><?= __('Libellé', 'docalist-core') ?></th>
                <th><?= __('Format', 'docalist-core') ?></th>
                <th><?= __('Type', 'docalist-core') ?></th>
                <th><?= __('Création', 'docalist-core') ?></th>
                <th><?= __('Mise à jour', 'docalist-core') ?></th>
            </tr>
        </thead>

        <?php
        $nb = 0;
        foreach($tables as $table) : /* @var $table TableInfo */
            if ($table->readonly() != $readonly) continue;
            ++$nb;

            $tableName = $table->name();

            $edit = esc_url($this->url('TableEdit', $tableName));
            $copy = esc_url($this->url('TableCopy',$tableName));
            $properties = esc_url($this->url('TableProperties', $tableName));
            $delete = esc_url($this->url('TableDelete',$tableName)); ?>

            <tr>
                <td class="column-title">
                    <strong>
                        <a href="<?= $edit ?>"><?= $tableName ?></a>
                    </strong>
                    <div class="row-actions">
                        <?php if (! $readonly) : ?>
                            <span class="edit">
                                <a href="<?= $edit ?>">
                                    <?= __('Modifier', 'docalist-core') ?>
                                </a>
                            </span>
                            |
                            <span class="properties">
                                <a href="<?= $properties ?>">
                                    <?= __('Propriétés', 'docalist-core') ?>
                                </a>
                            </span>
                            |
                            <span class="copy">
                                <a href="<?= $copy ?>">
                                    <?= __('Copier', 'docalist-core') ?>
                                </a>
                            </span>
                            |
                            <span class="delete">
                                <a href="<?= $delete ?>">
                                    <?= __('Supprimer', 'docalist-core') ?>
                                </a>
                            </span>
                        <?php else : ?>
                            <span class="show">
                                <a href="<?= $edit ?>">
                                    <?= __('Afficher', 'docalist-core') ?>
                                </a>
                            </span>
                            |
                            <span class="copy">
                                <a href="<?= $copy ?>">
                                    <?= __('Copier', 'docalist-core') ?>
                                </a>
                            </span>
                        <?php endif ?>
                    </div>
                </td>

                <td><?= $table->label() ?></td>
                <td><?= $table->format() ?></td>
                <td><?= $table->type() ?></td>
                <td><?= $table->creation() ?></td>
                <td><?= $table->lastupdate() ?></td>
            </tr>
        <?php endforeach ?>

        <?php if ($nb === 0) : ?>
            <tr>
                <td colspan="3">
                    <em><?= __('Aucune table définie. Vous pouvez créer une table personnalisée en recopiant une des tables prédéfinies listées ci-dessous.', 'docalist-core') ?></em>
                </td>
            </tr>
        <?php endif; ?>

        </table>
    <?php endforeach ?>
</div>