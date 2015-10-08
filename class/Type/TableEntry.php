<?php

/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Type;

use Docalist\Table\TableManager;
use Docalist\Table\TableInterface;
use Docalist\Forms\TableLookup;
use InvalidArgumentException;

/**
 * Un champ texte contenant un code provenant d'un table d'autorité associée
 * au champ.
 *
 * Exemples de champ de ce type dans docalist-biblio : genre, media, language,
 * format, etc.
 * Exemples de sous-champs : champ type des multifield, auteur.role, org.pays
 */
class TableEntry extends Text
{
    public function getSettingsForm()
    {
        // Récupère le formulaire par défaut
        $form = parent::getSettingsForm();

        // Ajoute un select permettant de choisir la table à utiliser
        $form->select('table')
            ->label($this->tableLabel())
            ->description(__("Choisissez la table d'autorité à utiliser pour ce champ. ", 'docalist-code'))
            ->attribute('class', 'table regular-text')
            ->options($this->getPossibleTables())
            ->firstOption(false);

        // ok
        return $form;
    }

    public function getEditorForm(array $options = null)
    {
        return new TableLookup($this->schema->name(), $this->schema->table());
    }

    public function getAvailableFormats()
    {
        return [
            'label' => __("Libellé qui figure dans la table d'autorité", 'docalist-core'),
            'code' => __('Code interne', 'docalist-core'),
            'label+description' => __("Libellé et description en bulle d'aide", 'docalist-core'),
        ];
    }

    public function getFormattedValue(array $options = null)
    {
        $format = $this->getOption('format', $options, $this->getDefaultFormat());

        $code = $this->value;

        if ($format === 'code') {
            return $code;
        }

        $table = $this->openTable();
        if (false === $entry = $table->find('*', 'code=' . $table->quote($code))) {
            return $code;
        }

        switch ($format) {
            // 'code': déjà traité plus haut
            case 'label':
                return $entry->label ?: $code;

            case 'label+description':
                return sprintf('<abbr title="%s">%s</abbr>', esc_attr($entry->description), $entry->label ?: $code);

            default:
                throw new InvalidArgumentException('Invalid format');
        }
    }

    /**
     * Ouvre la table d'autorité associée au champ.
     *
     * @return TableInterface
     */
    protected function openTable()
    {
        // Le nom de la table est de la forme "type:nom", on ne veut que le nom
        $table = explode(':', $this->schema->table())[1];

        // Ouvre la table
        return docalist('table-manager')->get($table);
    }

    /**
     * Retourne la liste des tables utilisables pour ce champ.
     *
     * La méthode recherche toutes les tables dont le type correspond au type
     * de table indiqué dans le schéma du champ. Les tables de conversion sont
     * ignorées.
     *
     * @return array Un tableau de la forme code => libellé contenant les tables
     * compatibles.
     */
    protected function getPossibleTables()
    {
        // Le nom de la table est de la forme "type:nom", on ne veut que le nom
        $table = explode(':', $this->schema->table())[1];

        // Détermine son type
        $tableManager = docalist('table-manager'); /* @var $tableManager TableManager */
        $type = $tableManager->table($table)->type();

        // Récupère toutes les tables qui ont le même type, sauf les tables de conversion
        $tables = [];
        foreach ($tableManager->tables($type) as $table) { /* @var $tableInfo TableInfo */
            if ($table->format() !== 'conversion') {
                $key = $table->format() . ':' . $table->name();
                $tables[$key] = sprintf('%s (%s)', $table->label(), $table->name());
            }
        }

        return $tables;
    }

    /**
     * Retourne le libellé à utiliser pour désigner la table d'autorité dans les formulaires.
     *
     * Par défaut, la méthode retourne "table d'autorité" mais les classes descendantes
     * peuvent la surcharger pour retourner un libellé plus spécifique: table des pays,
     * table des rôles, etc.
     *
     * @return string
     */
    protected function tableLabel()
    {
        return __("Table d'autorité", 'docalist-code');
    }
}
