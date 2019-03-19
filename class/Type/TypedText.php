<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Type\TypedValue;
use Docalist\Type\Text;

/**
 * Texte typé : un TypedValue qui a une valeur de type Text.
 *
 * @property Text $value Value Texte associé.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TypedText extends TypedValue
{
    public static function loadSchema(): array
    {
        return [
            'label' => __('Texte', 'docalist-core'),
            'description' => __('Texte et type de texte.', 'docalist-core'),
            'fields' => [
                'value' => [
                    'type' => Text::class,
                    'label' => __('Texte', 'docalist-core'),
                ],
            ],
        ];
    }
}
