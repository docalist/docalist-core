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

namespace Docalist\Type;

use Docalist\Forms\Container;
use Docalist\Forms\Element;
use Docalist\Forms\EntryPicker;
use Docalist\Forms\Select;
use Docalist\Schema\Schema;
use Docalist\Table\TableInterface;
use Docalist\Table\TableManager;
use InvalidArgumentException;
use stdClass;

/**
 * Un champ texte contenant un code provenant d'un table d'autorité associée au champ.
 *
 * Exemples de champ de ce type dans docalist-biblio : genre, media, language, format, etc.
 * Exemples de sous-champs : champ type des multifield, auteur.role, org.pays...
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TableEntry extends ListEntry
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

    private function getTable(): TableInterface
    {
        // Le nom de la table est de la forme "type:nom", on ne veut que le nom
        $table = explode(':', $this->getSchema()->table())[1];

        // Ouvre la table
        /** @var TableManager */
        $tableManager = docalist(TableManager::class);

        return $tableManager->get($table);
    }

    public function getEntries(): array
    {
        // Cette méthode n'est pas utilisée directement par TableEntry, mais elle peut être appellée par notre
        // classe parent (ListEntry), par exemple lorsque l'éditeur est paramétré sur 'select'.

        // Recherche le code et le label de toutes les entrées, triées par label en ignorant la casse
        return $this->getTable()->search('code,label', '', '_label');
    }

    /**
     * Retourne l'entrée de la table correspondant à la valeur actuelle du champ.
     *
     * @param string $returns Champ(s) à retourner, '*' par défaut.
     */
    public function getEntry($returns = '*'): mixed
    {
        $table = $this->getTable();

        // Recherche le code et retourne l'entrée correspondante
        return $table->find($returns, 'code='.$table->quote($this->phpValue));
    }

    public function getEntryLabel(): string
    {
        if (is_string($entry = $this->getEntry('label'))) { // pas false
            return $entry;
        }

        return $this->getPhpValue();
    }

    public function getSettingsForm(): Container
    {
        // Récupère le formulaire par défaut
        $form = parent::getSettingsForm();

        // Ajoute un select permettant de choisir la table à utiliser
        $form->select('table')
            ->setLabel($this->getTableLabel())
            ->setDescription(__("Choisissez la table d'autorité à utiliser pour ce champ.", 'docalist-core'))
            ->addClass('table')
            ->setOptions($this->getPossibleTables())
            ->setFirstOption(false);

        // ok
        return $form;
    }

    public function getAvailableEditors(): array
    {
        return parent::getAvailableEditors() + [
            'lookup' => __('Lookup dynamique', 'docalist-core'),
        ];
    }

    public function getDefaultEditor(): string
    {
        return 'lookup';
    }

    public function getEditorForm($options = null): Element
    {
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());
        switch ($editor) {
            case 'lookup':
                $form = new EntryPicker();
                break;

            case 'select': // Par défaut ListEntry met firstEntry à false, pour les tables on le veut à true
                $select = parent::getEditorForm($options); /** @var Select $select */

                return $select->setFirstOption(true);

            default:
                return parent::getEditorForm($options);
        }

        $form->setOptions($this->getSchema()->table());

        return $this->configureEditorForm($form, $options);
    }

    public function getAvailableFormats(): array
    {
        return parent::getAvailableFormats() + [
            'label+description' => __("Libellé de l'entrée et description en bulle d'aide", 'docalist-core'),
        ];
    }

    public function getFormattedValue($options = null): string
    {
        $format = $this->getOption('format', $options, $this->getDefaultFormat());

        switch ($format) {
            case 'label+description':
                /** @var stdClass|false */
                $entry = $this->getEntry();
                if ($entry === false) {
                    return $this->phpValue;
                }

                return sprintf(
                    '<abbr title="%s">%s</abbr>',
                    esc_attr($entry->description),
                    $entry->label ?: $this->phpValue
                );
        }

        return parent::getFormattedValue($options);
    }

    /**
     * Retourne la liste des tables utilisables pour ce champ.
     *
     * La méthode recherche toutes les tables dont le type correspond au type de table indiqué dans le schéma
     * du champ. Les tables de conversion sont ignorées.
     *
     * @return array<string,string> Un tableau de la forme code => libellé contenant les tables compatibles.
     */
    private function getPossibleTables(): array
    {
        // Le nom de la table est de la forme "type:nom", on ne veut que le nom
        $table = explode(':', $this->getSchema()->table())[1];

        // Détermine son type
        /** @var TableManager */
        $tableManager = docalist(TableManager::class);
        $type = $tableManager->table($table)->type->getPhpValue();

        // Récupère toutes les tables qui ont le même type, sauf les tables de conversion
        $tables = [];
        foreach ($tableManager->tables($type) as $table) {
            if ($table->format->getPhpValue() !== 'conversion') {
                $key = $table->format->getPhpValue().':'.$table->name->getPhpValue();
                $tables[$key] = sprintf('%s (%s)', $table->label->getPhpValue(), $table->name->getPhpValue());
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
     */
    protected function getTableLabel(): string
    {
        return __("Table d'autorité", 'docalist-core');
    }
}
