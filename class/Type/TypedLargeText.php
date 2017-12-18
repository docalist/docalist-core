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

use Docalist\Type\TypedText;
use Docalist\Type\TableEntry;
use Docalist\Type\LargeText;

/**
 * Texte large typé : un type composite associant un type provenant d'une table d'autorité à une valeur de
 * type LargeText.
 *
 * @property TableEntry $type   Type de texte.
 * @property LargeText  $value  Texte associé.
 */
class TypedLargeText extends TypedText
{
    public static function loadSchema()
    {
        return [
            'fields' => [
                'value' => [
                    'type' => 'Docalist\Type\LargeText',
                    'label' => __('Texte', 'docalist-core'),
                ],
            ],
        ];
    }
}
