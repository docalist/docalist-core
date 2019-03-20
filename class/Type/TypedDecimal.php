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
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TypedDecimal extends TypedNumber
{
    public static function loadSchema(): array
    {
        return [
            'label' => __('Chiffres clés', 'docalist-core'),
            'description' => __('Chiffres clés, nombres, dimensions, caractéristiques...', 'docalist-core'),
            'fields' => [
                'value' => [
                    'type' => Decimal::class,
                    'label' => __('Nombre', 'docalist-core'),
                    'description' => __('Nombre associé.', 'docalist-core'),
                ],
            ],
        ];
    }
}
