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
use Docalist\Type\FuzzyDate;

/**
 * Date typée : un type composite associant un champ TableEntry à une valeur de type FuzzyDate.
 *
 * @property TableEntry $type   Type de date.
 * @property FuzzyDate  $value  Date associée.
 */
class TypedFuzzyDate extends TypedText
{
    public static function loadSchema()
    {
        return [
            'label' => __('Date', 'docalist-core'),
            'description' => __('Date et type de date.', 'docalist-core'),
            'fields' => [
                'value' => [
                    'type' => 'Docalist\Type\FuzzyDate',
                    'label' => __('Date', 'docalist-core'),
                    'description' => __('Date au format AAAAMMJJ', 'docalist-core'),
                ]
            ]
        ];
    }
}
