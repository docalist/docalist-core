<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Type;

use Docalist\Forms\Select;
use Docalist\Forms\Radiolist;
use Docalist\Forms\Checklist;
use InvalidArgumentException;
use Docalist\Forms\Choice;

/**
 * Classe abstraite représentant un champ texte contenant un code choisi dans une liste de valeurs autorisées.
 */
abstract class ListEntry extends Text
{
    public static function loadSchema()
    {
        return [
            'label' => __('Entrée', 'docalist-core'),
            'description' => __('Choisissez dans la liste.', 'docalist-core'),
        ];
    }

    /**
     * Retourne la liste des entrées autorisées.
     *
     * @return array|string|callable La liste des entrées autorisées dans l'un des formats acceptés
     * par Choice::setOptions() :
     *
     * - un tableau de la forme 'code => label', par exemple : ['fr' => 'french', 'en' => english] ;
     * - une chaine de la forme 'table:countries' ou 'thesaurus:relators' ;
     * - un callable retournant le tableau des entrées, par exemple :
     *   function() { return ['fr' => 'french', 'en' => english]; }
     */
    abstract protected function getEntries();

    /**
     * Retourne le libellé de l'entrée en cours.
     *
     * @return string Retourne le libellé de l'entrée ou son code si ce n'est pas une entrée valide.
     */
    abstract public function getEntryLabel();

    public function getAvailableEditors()
    {
        return [
            'select'        => __('Menu déroulant contenant toutes les entrées', 'docalist-core'),
            'list'          => __('Liste à cocher', 'docalist-core'),
            'list-inline'   => __("Liste à cocher 'inline'", 'docalist-core'),
        ];
    }

    public function getDefaultEditor()
    {
        return 'select';
    }

    public function getEditorForm($options = null)
    {
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());
        switch ($editor) {
            case 'select':
                $editor = new Select();
                break;

            case 'list-inline':
            case 'radio-inline': // ancien nom
                $class = 'inline';

            case 'list':
            case 'radio': // ancien nom
                $editor = $this->getSchema()->collection() ? new CheckList() : new Radiolist();
                isset($class) && $editor->addClass('inline');
                break;

            default:
                throw new InvalidArgumentException("Invalid Entry editor '$editor'");
        }

        return $editor
            ->setName($this->schema->name())
            ->setOptions($this->getEntries())
            ->setLabel($this->getOption('label', $options))
            ->setDescription($this->getOption('description', $options));
    }

    public function getAvailableFormats()
    {
        return [
            'code'  => __("Code de l'entrée", 'docalist-core'),
            'label' => __("Libellé de l'entrée", 'docalist-core'),
        ];
    }

    public function getDefaultFormat()
    {
        return 'label';
    }

    public function getFormattedValue($options = null)
    {
        $format = $this->getOption('format', $options, $this->getDefaultFormat());

        switch ($format) {
            case 'code':
                return $this->phpValue;

            case 'label':
                return $this->getEntryLabel();
        }

        throw new InvalidArgumentException("Invalid Entry format '$format'");
    }
}
