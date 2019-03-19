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

use Docalist\Type\TypedText;
use Docalist\Type\LargeText;

/**
 * TypedLargeText : un TypedText qui a une valeur de type LargeText.
 *
 * @property LargeText $value Texte associé.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TypedLargeText extends TypedText
{
    public static function loadSchema(): array
    {
        return [
            'fields' => [
                'value' => [
                    'type' => LargeText::class,
                    'label' => __('Texte', 'docalist-core'),
                ],
            ],
        ];
    }
}
