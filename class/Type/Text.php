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

use Docalist\Type\Scalar;
use Docalist\Forms\Element;
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
    public function assign($value): void
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (! is_string($value)) {
            if (! is_scalar($value)) {
                throw new InvalidTypeException('string');
            }
            $value = (string) $value;
        }

        $this->phpValue = $value;
    }

    public function filterEmpty(bool $strict = true): bool
    {
        return ($this->phpValue === '');
    }

    public function getAvailableEditors(): array
    {
        return [
            'input'         => __('Zone de texte sur une seule ligne (par défaut)', 'docalist-core'),
            'input-small'   => __('Zone de texte sur une seule ligne (petite)', 'docalist-core'),
            'input-regular' => __('Zone de texte sur une seule ligne (moyenne)', 'docalist-core'),
            'input-large'   => __('Zone de texte sur une seule ligne (pleine largeur)', 'docalist-core'),
        ];
    }

    public function getEditorForm($options = null): Element
    {
        $editor = (string) $this->getOption('editor', $options, $this->getDefaultEditor());
        $css = '';
        switch ($editor) {
            case 'input':
                break;

            case 'input-small':
                $css = 'small-text';
                break;

            case 'input-regular':
                $css = 'regular-text';
                break;

            case 'input-large':
                $css = 'large-text';
                break;

            default:
                throw new InvalidArgumentException("Invalid Text editor '$editor'");
        }

        $form = new Input();

        return $form
            ->setName($this->schema->name())
            ->addClass($this->getEditorClass($editor, $css))
            ->setLabel($this->getOption('label', $options))
            ->setDescription($this->getOption('description', $options));
    }
}
