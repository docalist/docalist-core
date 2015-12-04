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

use Docalist\Core\AdminTables;
use Docalist\Table\TableInfo;

/**
 * Edite ou affiche le contenu d'une table d'autorité.
 *
 * @var AdminTables $this
 * @var string $tableName Nom de la table à modifier.
 * @var TableInfo $tableInfo Infos sur la table.
 * @var string[] $fields Liste des champs de la table.
 * @var object[] $data Liste des enregistrements de la table.
 * @var bool $readonly True si la table est en lecture seule.
 */

// l'ID de la table est dynamique pour pouvoir éventuellement utiliser
// l'option "persistentState" de HandsOnTable
$id = "table-$tableName";

// Url du répertoire "home" de handsontable
$base = plugins_url('docalist-core/lib/jquery-handsontable');

// Enqueue la CSS de HandsOnTable
wp_enqueue_style('handsontable-css', "$base/jquery.handsontable.full.css", false, '0.9.19');

// Enqueue le JS de HandsOnTable
wp_enqueue_script('handsontable', "$base/jquery.handsontable.full.js", ['jquery'], '0.9.19');
?>
<div class="wrap">
    <h1><?= $tableInfo->label() ?: $tableName ?></h1>

    <p class="description">
        <?php if ($readonly): ?>
            <?= __("Table prédéfinie : vous pouvez voir et copier les données de la table mais vous ne pouvez pas la modifier.", 'docalist-core') ?>
        <?php else: ?>
            <?= __('Table personnalisée : vous pouvez modifier la table ci-dessous comme dans un tableur. Utilisez le menu contextuel pour ajouter ou supprimer des lignes.', 'docalist-core') ?>
        <?php endif; ?>
    </p>

    <form method="post" id="editForm">
        <div id="<?=$id ?>"></div>

        <p class="buttons">
            <?php if ($readonly): ?>
                <a href="<?= esc_url($this->url('TablesList')) ?>" class="button">
                    <?= __('← Retour à la liste des tables', 'docalist-core') ?>
                </a>
            <?php else: ?>
                <button type="submit" class="button-primary">
                    <?= __('Enregistrer les modifications...', 'docalist-core') ?>
                </button>
            <?php endif; ?>
        </p>

        <input type="hidden" name="data" id="data" />
    </form>
</div>

<style data-jsfiddle="common">
    .handsontable .currentRow /*, .handsontable .currentCol */ {
        background-color: #F7F7F7;
    }
</style>

<script type="text/javascript">
(function($) {

    /**
      * Les données de la table. Le tableau est mis à jour directement par HOT.
      */
    var table = <?= json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    /**
     * La table HandsOnTable
     */

    <?php
    // Supprime les caractères spéciaux de jQuery
    // cf. https://learn.jquery.com/using-jquery-core/faq/how-do-i-select-an-element-by-an-id-that-has-characters-used-in-css-notation/
    $id = strtr($id, [
        ':' => '\\\\:',
        '.' => '\\\\.',
        ',' => '\\\\,',
        '[' => '\\\\[',
        ']' => '\\\\]',
    ]);
    ?>

    var grid = $('#<?=$id ?>');

    /**
     * Initialisation
     */
    $(document).ready(function () {
        grid.handsontable({
            // Données de la table
            data: table,

            // Définit le nom des colonnes
            colHeaders: <?= json_encode($fields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,

            // Quand la table est vide, il faut qu'on ait un schéma
            dataSchema: <?= json_encode(array_fill_keys($fields, null)) ?>,

            // En mode readonly, il faut désactiver l'édition pour chaque colonne
            <?php if ($readonly): ?>
                <?php
                    $columns = [];
                    foreach($fields as $field) {
                        $columns[] = ['data' => $field, 'readOnly' => true];
                    }
                ?>
                columns: <?= json_encode($columns) ?>,
            <?php endif; ?>

            // Désactive la poignée de recopie de cellule en mode readonly
            <?php if ($readonly): ?>
                fillHandle: false,
            <?php endif; ?>

            // Si la table est vide, ajoute une ligne sinon on ne peut rien éditer
            <?php if (empty($data) && ! $readonly): ?>
                minSpareRows : 1,
            <?php endif; ?>

            // Détermine la hauteur de la table
            height: function() {
                // Les lignes de la grille ont une hauteur de 23px
                // 69 : hauteur mini pour avoir 1 ligne d'entête + 2 records
                // 60 : espace visible sous la grille (bouton "save")
                var h = Math.max(69, $(window).height() - grid.offset().top - 60);

                // Ajuste la hauteur pour qu'on ne voit que des lignes entières
                var nb = Math.min(Math.ceil(h / 23), table.length + 1);

                return 4 + nb * 23;
            },

            // Affiche les numéros de ligne
            rowHeaders: true,

            // Menu contextuel
            <?php if (! $readonly): ?>
                contextMenu: {
                    items: {
                        row_above: {name: <?=json_encode(__('Insérer une ligne au dessus', 'docalist-core')) ?> },
                        row_below: {name: <?=json_encode(__('Ajouter une ligne en dessous', 'docalist-core')) ?> },
                        hsep1: '-----',
                        remove_row: {name: <?=json_encode(__('Supprimer les lignes sélectionnées', 'docalist-core')) ?> },
                        hsep2: '-----',
                        undo: {name: <?=json_encode(__('Annuler', 'docalist-core')) ?> },
                        redo: {name: <?=json_encode(__('Refaire', 'docalist-core')) ?> }
                    }
                },
            <?php endif; ?>

            // Mise en surbrillance de la ligne en cours
            currentRowClassName: 'currentRow',
            // currentColClassName: 'currentCol',

            // Permet de redimensionner les colonnes
            manualColumnResize: true,

            // Enregistre la largeur des colonnes dans localStorage
            // persistentState: true,

            // Conserver la sélection quand on clique ailleurs
            outsideClickDeselects: false,

            // Passe à la colonne suivante quand on fait enter
            enterMoves: {row:0, col:1},

            // Passe à la ligne suivante quand on fait tab sur la dernière
            autoWrapRow: true,
        })

        // Sélectionne la première cellule une fois la grille construite
        .handsontable("selectCell", 0, 0);

        // sanity check ie < 8
        if (typeof JSON === 'undefined') {
            alert("Votre navigateur ne dispose pas de l'objet JSON : la table ne pourra pas être enregistrée");
        }

        /**
         * Enregistrer les modifications
         */
        $('#editForm').submit(function() {
            var post = {
                // On ne peut pas envoyer la table directement comme des données
                // $_POST car php a une limite (max_input_vars) sur le nombre de
                // variables qu'on peut passer dans une requête et chaque
                // cellule compte comme une variable (cf prisme#66).
                // Du coup on sérialise en ajax.
                data: JSON.stringify(table),

                // En envoie également le nombre d'entrées présentes dans la
                // table pour permettre au controlleur de faire une vérif.
                count: table.length
            }

            $.post('', post, function(result) {
                // Le contrôleur nous retourne :
                // soit {success: true, url:'location'} -> rediriger vers l'url indiquée
                // soit {success: false, error: 'erreur'} -> afficher l'erreur
                if (result.success) {
                    window.location.replace(result.url);
                } else {
                    alert('error : ' + result.error);
                }

            });
            return false;
        });
    });
}(jQuery));
</script>