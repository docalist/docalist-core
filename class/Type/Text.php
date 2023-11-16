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

use Docalist\Forms\Element;
use Docalist\Forms\Input;
use Docalist\Type\Exception\InvalidTypeException;
use Docalist\Type\Scalar as ScalarType;

/**
 * Type chaine de caractères.
 *
 * @extends ScalarType<string>
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Text extends ScalarType
{
    public static function getClassDefault(): string
    {
        return '';
    }

    public function assign($value): void
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (!is_string($value)) {
            if (!is_scalar($value)) {
                throw new InvalidTypeException('string');
            }
            $value = (string) $value;
        }

        $this->phpValue = $value;
    }

    public function getPhpValue(): string
    {
        return $this->phpValue;
    }

    public function getFormattedValue($options = null): string
    {
        return $this->phpValue;
    }

    public function filterEmpty(bool $strict = true): bool
    {
        return '' === $this->phpValue;
    }

    public function getAvailableEditors(): array
    {
        return [
            'input' => __('Zone de texte sur une seule ligne (par défaut)', 'docalist-core'),
            'input-small' => __('Zone de texte sur une seule ligne (petite)', 'docalist-core'),
            'input-regular' => __('Zone de texte sur une seule ligne (moyenne)', 'docalist-core'),
            'input-large' => __('Zone de texte sur une seule ligne (pleine largeur)', 'docalist-core'),
        ];
    }

    public function getEditorForm($options = null): Element
    {
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());
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
                return parent::getEditorForm($options);
        }

        $form = new Input();
        !empty($css) && $form->addClass($css);

        return $this->configureEditorForm($form, $options);
    }
}
