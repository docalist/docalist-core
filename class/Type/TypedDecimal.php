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

use Docalist\Type\TypedNumber;
use Docalist\Type\TableEntry;
use Docalist\Type\Decimal;

/**
 * Nombre typé : un type composite associant un type provenant d'une table d'autorité de type number à une valeur
 * numérique de de type Decimal : chiffres clés, données chiffrées, dimensions, caractéristiques...
 *
 * La table associée contient une colonne format qui indique comment formatter les entrées.
 *
 * @property TableEntry $type   Type    Type de chiffre clé.
 * @property Decimal    $value  Value   Nombre associé.
 */
class TypedDecimal extends TypedNumber
{
    public static function loadSchema()
    {
        return [
            'label' => __('Chiffres clés', 'docalist-core'),
            'description' => __('Chiffres clés, nombres, dimensions, caractéristiques...', 'docalist-core'),
            'fields' => [
                'value' => [
                    'type' => 'Docalist\Type\Decimal',
                    'label' => __('Nombre', 'docalist-core'),
                    'description' => __('Nombre associé.', 'docalist-core'),
                ],
            ],
        ];
    }
}
