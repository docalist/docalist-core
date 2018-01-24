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
use Docalist\Type\PhoneNumber;

/**
 * Numéro de téléphone typé : un type composite associant un type provenant d'une table d'autorité à une valeur de
 * type PhoneNumber.
 *
 * @property TableEntry     $type   Type de numéro (standard, ligne directe, mobile...)
 * @property PhoneNumber    $value  Numéro de téléphone associé.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TypedPhoneNumber extends TypedText
{
    /*
     * On hérite de TypedText et non pas de TypedNumber car :
     * - un numéro de téléphone n'est pas un nombre (on ne peut pas faire de validate_float dessus)
     * - la table d'autorité associée ne contient pas de colonne format
     */

    public static function loadSchema()
    {
        return [
            'label' => __('Téléphone', 'docalist-core'),
            'description' => __('Numéros de téléphone.', 'docalist-core'),
            'fields' => [
                'value' => [
                    'type' => 'Docalist\Type\PhoneNumber',
                ],
            ],
        ];
    }
}
