<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Type\TypedText;
use Docalist\Type\TableEntry;
use Docalist\Type\LargeText;

/**
 * Texte large typé : un type composite associant un type provenant d'une table d'autorité à une valeur de
 * type LargeText.
 *
 * @property TableEntry $type   Type de texte.
 * @property LargeText  $value  Texte associé.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TypedLargeText extends TypedText
{
    public static function loadSchema()
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
