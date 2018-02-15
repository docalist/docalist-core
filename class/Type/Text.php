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

use Docalist\Forms\Input;
use Docalist\Type\Exception\InvalidTypeException;
use InvalidArgumentException;

/**
 * Type chaine de caractères.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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
                $form = new Input();
                break;

            case 'input-small':
                $form = new Input();
                $form->addClass('small-text');
                break;

            case 'input-regular':
                $form = new Input();
                $form->addClass('regular-text');
                break;

            case 'input-large':
                $form = new Input();
                $form->addClass('large-text');
                break;

            default:
                throw new InvalidArgumentException("Invalid Text editor '$form'");
        }

        return $form
            ->setName($this->schema->name())
            ->addClass($this->getEditorClass($editor))
            ->setLabel($this->getOption('label', $options))
            ->setDescription($this->getOption('description', $options));
    }
}
