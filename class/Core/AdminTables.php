<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Core;

use Docalist\AdminPage;
use Docalist\Http\Response;
use Docalist\Table\TableInfo;
use Docalist\Table\TableManager;
use Exception;

/**
 * Gestion des tables d'autorité.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class AdminTables extends AdminPage
{
    protected $capability = [
        'default' => 'manage_options',
    ];

    public function __construct(Private TableManager $tableManager)
    {
        parent::__construct(
            'docalist-tables',                          // ID
            'options-general.php',                      // page parent
            __("Tables d'autorité", 'docalist-core')    // libellé menu
        );
    }

    protected function getDefaultAction()
    {
        return 'TablesList';
    }

    /**
     * Retourne l'objet TableInfo d'une table.
     *
     * @param string $tableName
     *
     * @return TableInfo
     */
    protected function tableInfo($tableName)
    {
        return $this->tableManager->table($tableName);
    }

    /**
     * Liste des tables d'autorité.
     */
    public function actionTablesList(): Response
    {
        // Format en cours
        // $format = empty($_GET['format']) ? null : $_GET['format'];
        $format = $_GET['format'] ?? '';

        // Type en cours
        // $type = empty($_GET['type']) ? null : $_GET['type'];
        $type = $_GET['type'] ?? '';

        // Readonly ?
        $readonly = null;
        isset($_GET['readonly']) && $_GET['readonly'] === '0' && $readonly = false;
        isset($_GET['readonly']) && $_GET['readonly'] === '1' && $readonly = true;

        // Liste des formats disponibles
        $formats = $this->tableManager->formats();

        // Liste des types disponibles
        $types = $this->tableManager->types($format);

        // Tri en cours
        $sort = isset($_GET['sort']) ? $_GET['sort'] : 'label';
        $order = isset($_GET['order']) ? $_GET['order'] : 'asc';

        return $this->view('docalist-core:table/list', [
            'tables' => $this->tableManager->tables($type, $format, $readonly, "$sort $order"),
            'formats' => $formats,
            'types' => $types,
            'format' => $format,
            'type' => $type,
            'readonly' => $readonly,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    /**
     * Modifie le contenu d'une table d'autorité.
     */
    public function actionTableEdit(string $tableName): Response
    {
        // Vérifie que la table à modifier existe
        $tableInfo = $this->tableInfo($tableName);

        // Ouvre la table
        $table = $this->tableManager->get($tableName);

        // Gère la sauvegarde
        if ($this->isPost()) {
            // Récupère les données de la table
            if (! isset($_POST['data'])) {
                return $this->json([
                    'success' => false,
                    'error' => __('Aucune donnée transmise', 'docalist-core'),
                ]);
            }
            $data = wp_unslash($_POST['data']);
            $data = json_decode($data);
            if (!is_array($data)) {
                return $this->json([
                    'success' => false,
                    'error' => __('Unable to json_decode data', 'docalist-core'),
                ]);
            }

            // Vérifie que le nombre d'entrées est correct
            if (! isset($_POST['count'])) {
                return $this->json([
                    'success' => false,
                    'error' => __('count non transmis', 'docalist-core'),
                ]);
            }
            $count = (int) $_POST['count'];
            if ($count !== count($data)) {
                return $this->json([
                    'success' => false,
                    'error' => 'count error : reçu : ' . count($data) . ', attendu : ' . $count,
                ]);
            }

            // Enregistre les données de la table
            try {
                $this->tableManager->update($tableName, null, null, $data);
            } catch (Exception $e) {
                return $this->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);
            }

            return $this->json([
                'success' => true,
                'url' => $this->getUrl('TablesList'),
            ]);
        }

        // Récupère la liste des champs
        $fields = $table->fields();

        // Récupère les données de la table
        // On veut un tableau d'objets, pas un tableau associatif
        $data = $table->search('ROWID,' . implode(',', $fields));
        $data = array_values($data);

        // Affiche l'éditeur
        return $this->view('docalist-core:table/edit', [
            'tableName' => $tableName,
            'tableInfo' => $tableInfo,
            'fields' => $fields,
            'data' => $data,
            'readonly' => $tableInfo->readonly->getPhpValue(),
        ]);
    }

    /**
     * Copie une table.
     */
    public function actionTableCopy(string $tableName): Response
    {
        $tableInfo = $this->tableInfo($tableName);

        // Requête post : copie la table
        $error = '';
        if ($this->isPost()) {
            $_POST = wp_unslash($_POST);

            $name = $_POST['name'];
            $label = $_POST['label'];
            $nodata = (bool) $_POST['nodata'];

            try {
                $this->tableManager->copy($tableName, $name, $label, $nodata);

                return $this->redirect($this->getUrl('TablesList'), 303);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        // Suggère un nouveau nom pour la table
        for ($i = 2;; ++$i) {
            $name = "$tableName-$i";
            if (! $this->tableManager->has($name)) {
                break;
            }
        }
        $tableInfo->name->assign($name);
        $tableInfo->label->assign(sprintf(__('Copie de %s', 'docalist-core'), $tableInfo->label->getPhpValue()));

        return $this->view('docalist-core:table/copy', [
            'tableName' => $tableName,
            'tableInfo' => $tableInfo,
            'error' => $error,
        ]);
    }

    /**
     * Modifie les propriétés d'une table d'autorité.
     */
    public function actionTableProperties(string $tableName): Response
    {
        $tableInfo = $this->tableInfo($tableName);

        $error = '';
        if ($this->isPost()) {
            $_POST = wp_unslash($_POST);

            $name = $_POST['name'];
            $label = $_POST['label'];

            try {
                $this->tableManager->update($tableName, $name, $label);

                return $this->redirect($this->getUrl('TablesList'), 303);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            $tableInfo->name = $name;
            $tableInfo->label = $label;
        }

        return $this->view('docalist-core:table/properties', [
            'tableName' => $tableName,
            'tableInfo' => $tableInfo,
            'error' => $error,
        ]);
    }

    /**
     * Supprime une table d'autorité.
     */
    public function actionTableDelete(string $tableName, bool $confirm = false): Response
    {
        // Vérifie que la table existe
        $this->tableInfo($tableName);

        // Demande confirmation
        if (! $confirm) {
            $msg = __('La table "%s" va être supprimée. ', 'docalist-core');
            $msg .= __('Cette action ne peut pas être annulée.', 'docalist-core');
            $msg .= '<br />';
            $msg .= __('Assurez-vous que cette table n\'est plus utilisée.', 'docalist-core');

            $href = $this->getUrl('TableProperties', $tableName);
            $msg = sprintf($msg, $tableName, $href);

            return $this->confirm($msg, __('Supprimer une table', 'docalist-core'));
        }

        // Essaie de supprimer la table
        try {
            $this->tableManager->delete($tableName);

            return $this->redirect($this->getUrl('TablesList'), 303);
        } catch (Exception $e) {
            return $this->view('docalist-core:error', [
                'h2' => __('Supprimer une table', 'docalist-core'),
                'h3' => __('Erreur', 'docalist-core'),
                'message' => $e->getMessage(),
                'back' => $this->getUrl('TablesList'),
            ]);
        }
    }
}
