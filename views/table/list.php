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
use Docalist\Table\TableManager;

/**
 * Liste des tables d'autorité.
 *
 * @param TableInfo[] $tables Liste des tables.
 * @param string[] $formats Formats de tables disponibles.
 * @param string[] $types Types de tables disponibles.
 * @param string $format Format en cours.
 * @param string $type Type en cours.
 * @param string $readonly Readonly ?
 */

$tableManager = docalist('table-manager'); /* @var $tableManager TableManager */
?>
<style>
    .fixed .column-readonly {width: 10%}
    .fixed .column-format {width: 10%}
    .fixed .column-type {width: 10%}
    .fixed .column-creation {width: 10%}
    .fixed .column-lastupdate {width: 10%}
    @media screen and ( max-width: 1100px ) {
        .fixed .column-creation,
        .fixed .column-lastupdate {
        	display: none;
        }
    }
    @media screen and ( max-width: 782px ) {
        .fixed .column-readonly,
        .fixed .column-format,
        .fixed .column-type
        {
        	display: none;
        }
    }
</style>
<div class="wrap">
    <?= screen_icon() ?>
    <h2>
        <?= $format ? formatFormat($format) : __("Gestion des tables d'autorité", 'docalist-core') ?>
        <?php
            if ($type) {
                echo ' - ', formatType($type);
            }
        ?>
    </h2>

    <p class="description"><?= __("
        Cette page vous permet de gérer les tables Docalist.

        Il existe deux types de tables : les tables prédéfinies (créées par des
        plugins) et les tables personnalisées (celles que vous créez en faisant
        une copie d'une table existante).

        Vous pouvez modifier comme vous le souhaitez les propriétés et le contenu
        des tables personnalisées mais vous ne pouvez pas modifier les tables
        prédéfinies (vous pouvez seulement afficher leur contenu et les copier).

        Utilisez les filtres pour sélectionner les tables affichées.
    ", 'docalist-core') ?>
    </p>

    <form id="posts-filter" method="get">
        <input type="hidden" name="page" value="<?=$_GET['page']?>">
        <?php if ($sort):?>
            <input type="hidden" name="sort" value="<?=$sort?>">
        <?php endif; ?>
        <?php if ($order):?>
            <input type="hidden" name="order" value="<?=$order?>">
        <?php endif; ?>

        <div class="tablenav top">
            <div class="alignleft actions">

                <?php
                /** --------------------------------------------------------------
                 *  Sélecteur Readonly
                 *  --------------------------------------------------------------*/
                ?>
                <select name="readonly" onchange="this.form.submit()">
                    <?php foreach([null, '0', '1'] as $mode): ?>
                        <?php
                            $count = count($tableManager->tables($type, $format, $mode));
                            if ($count === 0) {
                                continue;
                            }
                        ?>
                        <option value="<?=$mode?>" <?php selected($readonly, $mode) ?>>
                            <?=formatReadonly($mode) ?>
                            (<?= number_format_i18n($count) ?>)
                        </option>
                    <?php endforeach ?>
                </select>

                <?php
                /** --------------------------------------------------------------
                 *  Sélecteur Format
                 *  --------------------------------------------------------------*/
                ?>
                <select name="format" onchange="this.form.submit()">
                    <?php array_unshift($formats, null)?>
                    <?php foreach($formats as $fmt): ?>
                        <?php
                            $count = count($tableManager->tables($type, $fmt, $readonly));
                            if ($count === 0) {
                                continue;
                            }
                        ?>
                        <option value="<?=$fmt?>" <?php selected($format, $fmt) ?>>
                            <?=formatFormat($fmt) ?>
                            (<?= number_format_i18n($count) ?>)
                        </option>
                    <?php endforeach ?>
                </select>

                <?php
                /** --------------------------------------------------------------
                 *  Sélecteur type
                 *  --------------------------------------------------------------*/
                ?>
                <select name="type" onchange="this.form.submit()">
                    <?php array_unshift($types, null)?>
                    <?php foreach($types as $typ): ?>
                        <?php
                            $count = count($tableManager->tables($typ, $format, $readonly));
                            if ($count === 0) {
                                continue;
                            }
                        ?>
                        <option value="<?=$typ?>" <?php selected($type, $typ) ?>>
                            <?=formatType($typ) ?>
                            (<?= number_format_i18n($count) ?>)
                        </option>
                    <?php endforeach ?>
                </select>

                <?php
                /** --------------------------------------------------------------
                 *  Bouton "réinitialiser les filtres"
                 *  --------------------------------------------------------------*/
                ?>
                <?php if ($format || $type || !is_null($readonly) || !($sort === 'label' && $order === 'asc')): ?>
                    <a href="<?=esc_url($this->url())?>" class="button action">
                        <?=__('Réinitialiser les filtres', 'docalist-core')?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="tablenav-pages one-page">
                <span class="displaying-num"><?=sprintf(__('%d tables', 'docalist-core'), count($tables))?></span>
            </div>
        </div>

        <table class="widefat fixed">

        <thead><?php tableHeader($sort, $order) ?></thead>
        <tfoot><?php tableHeader($sort, $order) ?></tfoot>

        <?php
        $nb = 0;
        foreach($tables as $table) : /* @var $table TableInfo */
            ++$nb;

            $tableName = $table->name();

            $edit = esc_url($this->url('TableEdit', $tableName));
            $copy = esc_url($this->url('TableCopy',$tableName));
            $properties = esc_url($this->url('TableProperties', $tableName));
            $delete = esc_url($this->url('TableDelete',$tableName)); ?>

            <tr class="<?= $nb % 2 ? 'alternate' : '' ?>">
                <td class="column-title">
                    <strong>
                        <a class="row-title" href="<?= $edit ?>"><?= $table->label() ?></a> - <?= $tableName ?>
                    </strong>
                    <div class="row-actions">
                        <?php if ($table->readonly()) : ?>
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
                        <?php else : ?>
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
                        <?php endif ?>
                    </div>
                </td>
                <td class="column-readonly">
                    <?php $href = add_query_arg(['readonly' => $table->readonly()]); ?>
                    <a href="<?=esc_url($href)?>"><?= formatReadonly($table->readonly()) ?></a>
                </td>
                <td class="column-format">
                    <?php $href = add_query_arg(['format' => $table->format()]); ?>
                    <a href="<?=esc_url($href)?>"><?= formatFormat($table->format()) ?></a>
                </td>
                <td class="column-type">
                    <?php $href = add_query_arg(['type' => $table->type()]); ?>
                    <a href="<?=esc_url($href)?>"><?= formatType($table->type()) ?></a>
                </td>
                <td class="column-creation">
                    <?= formatDate($table->creation()) ?>
                </td>
                <td class="column-lastupdate">
                    <?= formatDate($table->lastupdate()) ?>
                </td>
            </tr>
        <?php endforeach ?>

        <?php if ($nb === 0) : ?>
            <tr>
                <td colspan="3">
                    <em><?= __('Aucune table ne correspond aux critères indiqués.', 'docalist-core') ?></em>
                </td>
            </tr>
        <?php endif; ?>

        </table>

        <div class="tablenav bottom">
            <div class="tablenav-pages one-page">
                <span class="displaying-num"><?=sprintf(__('%d tables', 'docalist-core'), count($tables))?></span>
            </div>
        </div>
    </form>
</div>
<?php
    function formatFormat($format) {
        switch($format) {
            case null:
                return __('Tous les formats', 'docalist-core');

            case 'conversion':
                return __('Table de conversion', 'docalist-core');

            case 'master':
                return __('Table maître', 'docalist-core');

            case 'table':
                return __('Table d\'autorité', 'docalist-core');

            case 'thesaurus':
                return __('Thesaurus', 'docalist-core');

            default:
                return ucfirst($format);
        }
    }

    function formatType($type) {
        switch($type) {
            case null:
                return __('Tous les contenus', 'docalist-core');

            case 'languages':
                return __('Langues', 'docalist-core');

            case 'countries':
                return __('Pays', 'docalist-core');

            case 'content':
                return __('Types de contenus', 'docalist-core');

            case 'format':
                return __('Étiquettes de format', 'docalist-core');

            case 'roles':
                return __('Étiquettes de rôle', 'docalist-core');

            case 'thesaurus':
                return __('Mots-clés', 'docalist-core');

            case 'genres':
                return __('Genres ', 'docalist-core');

            case 'topics':
                return __('Liste de vocabulaires', 'docalist-core');

            case 'medias':
                return __('Supports', 'docalist-core');

            case 'dates':
                return __('Types de dates', 'docalist-core');

            case 'links':
                return __('Types de liens', 'docalist-core');

            case 'numbers':
                return __('Types de numéros', 'docalist-core');

            case 'extent':
                return __('Étendues', 'docalist-core');

            case 'relations':
                return __('Types de relations', 'docalist-core');

            case 'titles':
                return __('Types de titres', 'docalist-core');

            case 'master':
                return __('Tables', 'docalist-core');

            default:
                return '*****' . ucfirst($type);
        }
    }

    function formatReadonly($readonly) {
        if (is_null($readonly)) { // switch=loose comparision donc '' = '0' = null
            return __('Tous les types', 'docalist-core');
        }

        switch($readonly) {
            case '0':
                return __('Personnalisée', 'docalist-core');

            case '1':
                return __('Prédéfinie', 'docalist-core');

            default:
                return var_export($readonly);
        }
    }

    function formatDate($date) {
        return '<abbr title="'. substr($date, 11) .'">' . substr($date, 0, 10) . '</abbr>';
    }

    function tableHeader($sort, $order) { ?>
        <tr>
            <?php sortableColumn($sort, $order, 'label', __('Nom', 'docalist-core'), 'asc') ?>
            <th class="column-readonly"><?= __('Type', 'docalist-core') ?></th>
            <th class="column-format"><?= __('Format', 'docalist-core') ?></th>
            <th class="column-type"><?= __('Contenu', 'docalist-core') ?></th>
            <?php sortableColumn($sort, $order, 'creation', __('Création', 'docalist-core'), 'desc') ?>
            <?php sortableColumn($sort, $order, 'lastupdate', __('Mise à jour', 'docalist-core'), 'desc') ?>
        </tr>
    <?php }

    function sortableColumn($sort, $order, $name, $label, $default = 'asc') { ?>
        <?php
            $sorted = $sort === $name; // la colonne en cours est triée ?
            $order = ($sorted && $order) ? $order : $default;
            $reverse = ($order === 'asc') ? 'desc' : 'asc';

            if ($sort === 'label' && $order === 'asc') { // c'est le tri par défaut, simplifie l'url
                $href = add_query_arg(['sort' => null, 'order' => null]);
            } else {
                $href = add_query_arg(['sort' => $name, 'order' => $sorted ? $reverse : $order]);
            }
        ?>
        <th class="manage-column column-<?=$name?> <?=$sorted ? 'sorted' : 'sortable'?> <?=$sorted ? $order : $reverse ?>">
            <a href="<?=esc_url($href)?>">
                <span><?=$label?></span>
                <span class="sorting-indicator"></span>
            </a>
        </th>
        <?php
    }
?>