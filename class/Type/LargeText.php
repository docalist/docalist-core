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

use Docalist\Forms\Textarea;
use Docalist\Forms\WPEditor;
use InvalidArgumentException;

/**
 * Un bloc de texte multiligne contenant ou non du code html.
 */
class LargeText extends Text
{
    public function getAvailableEditors()
    {
        return [
            'textarea'          => __('Zone de texte sur plusieurs lignes', 'docalist-core'),
            'wpeditor'          => __('Editeur WordPress', 'docalist-core'),
            'wpeditor-teeny'    => __('Editeur WordPress simplifié', 'docalist-core'),
        ];
    }

    public function getEditorForm(array $options = null)
    {
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());
        $name = isset($this->schema) ? $this->schema->name() : $this->randomId();
        switch ($editor) {
            case 'textarea':        return new Textarea($name);
            case 'wpeditor':        return new WPEditor($name);
            case 'wpeditor-teeny':  return (new WPEditor($name))->setTeeny();
        }

        throw new InvalidArgumentException('Invalid editor');
    }
}
