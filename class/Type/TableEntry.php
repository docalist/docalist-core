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

use Docalist\Schema\Schema;
use Docalist\Table\TableManager;
use Docalist\Forms\EntryPicker;
use Docalist\Forms\Select;
use Docalist\Forms\Radiolist;
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
    public function __construct($value = null, Schema $schema = null)
    {
        parent::__construct($value, $schema);

        // Garantit qu'on a un schéma et que la table à utiliser est indiquée
        if (is_null($schema) || empty($schema->table())) {
            $field = $schema ? ($schema->name() ?: $schema->label()) : '';
            throw new InvalidArgumentException("Schema property 'table' is required for a TableEntry field '$field'.");
        }
    }

    public static function loadSchema()
    {
        return [
            'label' => __('Entrée', 'docalist-core'),
            'description' => __('Choisissez dans la liste.', 'docalist-core'),
        ];
    }

    public function getSettingsForm()
    {
        // Récupère le formulaire par défaut
        $form = parent::getSettingsForm();

        // Ajoute un select permettant de choisir la table à utiliser
        $form->select('table')
            ->setLabel($this->getTableLabel())
            ->setDescription(__("Choisissez la table d'autorité à utiliser pour ce champ.", 'docalist-core'))
            ->addClass('table regular-text')
            ->setOptions($this->getPossibleTables())
            ->setFirstOption(false);

        // ok
        return $form;
    }

    public function getAvailableEditors()
    {
        return [
            'lookup' => __('Lookup dynamique', 'docalist-core'),
            'select' => __('Menu déroulant contenant toutes les entrées', 'docalist-core'),
            'radio' => __('Liste de boutons radios avec toutes les entrées', 'docalist-core'),
            'radio-inline' => __("Boutons radios 'inline'", 'docalist-core'),
        ];
    }

    public function getEditorForm($options = null)
    {
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());

        switch ($editor) {
            case 'lookup':
                $editor = new EntryPicker();
                break;

            case 'select':
                $editor = new Select();
                break;

            case 'radio':
                $editor = new Radiolist();
                break;

            case 'radio-inline':
                $editor = new Radiolist();
                $editor->addClass('inline');
                break;

            default:
                throw new InvalidArgumentException("Invalid TableEntry editor '$editor'");
        }

        /* @var EntryPicker $ui */
        return $editor
            ->setName($this->schema->name())
            ->setOptions($this->schema->table())
            ->setLabel($this->getOption('label', $options))
            ->setDescription($this->getOption('description', $options));
    }

    public function getAvailableFormats()
    {
        return [
            'label' => __("Libellé qui figure dans la table d'autorité", 'docalist-core'),
            'code' => __('Code interne', 'docalist-core'),
            'label+description' => __("Libellé et description en bulle d'aide", 'docalist-core'),
        ];
    }

    public function getFormattedValue($options = null)
    {
        $format = $this->getOption('format', $options, $this->getDefaultFormat());

        switch ($format) {
            case 'code':
                return $this->value;

            case 'label':
                return $this->getEntry('label') ?: $this->value;

            case 'label+description':
                $entry = $this->getEntry();
                if ($entry === false) {
                    return $this->value;
                }

                return sprintf(
                    '<abbr title="%s">%s</abbr>',
                    esc_attr($entry->description),
                    $entry->label ?: $this->value
                );
        }

        throw new InvalidArgumentException("Invalid TableEntry format '$format'");
    }

    /**
     * Retourne l'entrée dans la table correspondant à la valeur actuelle du champ.
     *
     * @param string $returns Champ(s) à retourner, '*' par défaut.
     *
     * @return mixed
     */
    public function getEntry($returns = '*')
    {
        // Le nom de la table est de la forme "type:nom", on ne veut que le nom
        $table = explode(':', $this->schema->table())[1];

        // Ouvre la table
        $table = docalist('table-manager')->get($table);

        // Recherche le code et retourne l'entrée correspondante
        return $table->find($returns, 'code=' . $table->quote($this->value));
    }

    /**
     * Retourne le libellé de l'entrée.
     *
     * @return string Retourne le libellé de l'entrée ou son code si l'entrée ne figure
     * pas dans la table d'autorité associée.
     */
    public function getEntryLabel()
    {
        return $this->getEntry('label') ?: $this->value;
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
    protected function getTableLabel()
    {
        return __("Table d'autorité", 'docalist-core');
    }
}
