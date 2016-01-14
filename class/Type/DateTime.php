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

use Docalist\Forms\Input;

/**
 * Une date/heure stockée sous forme de chaine au format 'yyyy-MM-dd HH:mm:ss'.
 *
 * Exemple : "2014-09-02 11:19:24"
 */
class DateTime extends Text
{
    public function getAvailableEditors()
    {
        return  parent::getAvailableEditors() + [
            'datetime-local' => __('Champ date/heure', 'docalist-core'),
            'datetime' => __('Champ date/heure + fuseau horaire', 'docalist-core'),
        ];
    }

    public function getEditorForm($options = null)
    {
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());

        switch ($editor) {
            case 'datetime':
            case 'datetime-local':
                $type = $editor;
                break;

            default:
                return parent::getEditorForm($options);
        }

        $editor = new Input();

        return $editor
            ->setName($this->schema->name())
            ->setLabel($this->getOption('label', $options))
            ->setDescription($this->getOption('description', $options))
            ->setAttribute('type', $type);
    }
}
