<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Forms\Select;
use Docalist\Forms\Radiolist;
use Docalist\Forms\Checklist;
use InvalidArgumentException;

/**
 * Classe de base abstraite représentant un champ texte permettant à l'utilisateur de choisir une entrée dans une
 * liste de valeurs autorisées.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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
     * @return array Un tableau de la forme 'code => label', par exemple : ['fr' => 'french', 'en' => english].
     */
    protected function getEntries()
    {
        return [];
    }

    /**
     * Retourne le libellé de l'entrée en cours.
     *
     * @return string Retourne le libellé de l'entrée ou son code si ce n'est pas une entrée valide.
     */
    public function getEntryLabel()
    {
        $entries = $this->getEntries();

        return isset($entries[$this->phpValue]) ? $entries[$this->phpValue] : $this->phpValue;
    }

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
        $css = '';
        switch ($editor) {
            case 'select':
                $form = new Select();
                break;

            case 'list-inline':
            case 'radio-inline': // ancien nom
                $css = 'inline';
                // Pas de break

            case 'list':
            case 'radio': // ancien nom
                $form = $this->getSchema()->collection() ? new CheckList() : new Radiolist();
                break;

            default:
                throw new InvalidArgumentException("Invalid Entry editor '$editor'");
        }

        return $form
            ->setName($this->schema->name())
            ->addClass($this->getEditorClass($editor, $css))
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

        return parent::getFormattedValue($options);
    }
}
