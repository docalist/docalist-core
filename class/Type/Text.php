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

use Docalist\Forms\Input;
use Docalist\Type\Exception\InvalidTypeException;
use InvalidArgumentException;

/**
 * Type chaine de caractères.
 */
class Text extends Scalar
{
    public function assign($value)
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (! is_string($value)) {
            if (! is_scalar($value)) {
                throw new InvalidTypeException('string');
            }
            $value = (string) $value;
        }

        $this->phpValue = $value;

        return $this;
    }

    public function getAvailableEditors()
    {
        return [
            'input'         => __('Zone de texte sur une seule ligne (par défaut)', 'docalist-core'),
            'input-small'   => __('Zone de texte sur une seule ligne (petite)', 'docalist-core'),
            'input-regular' => __('Zone de texte sur une seule ligne (moyenne)', 'docalist-core'),
            'input-large'   => __('Zone de texte sur une seule ligne (pleine largeur)', 'docalist-core'),
        ];
    }

    public function getEditorForm($options = null)
    {
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());
        switch ($editor) {
            case 'input':
                $editor = new Input();
                break;

            case 'input-small':
                $editor = new Input();
                $editor->addClass('small-text');
                break;

            case 'input-regular':
                $editor = new Input();
                $editor->addClass('regular-text');
                break;

            case 'input-large':
                $editor = new Input();
                $editor->addClass('large-text');
                break;

            default:
                throw new InvalidArgumentException("Invalid Text editor '$editor'");
        }

        return $editor
            ->setName($this->schema->name())
            ->setLabel($this->getOption('label', $options))
            ->setDescription($this->getOption('description', $options));
    }
}
