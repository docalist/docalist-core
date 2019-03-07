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
use Docalist\Type\FuzzyDate;

/**
 * TypedFuzzyDate : un TypedValue qui a une valeur de type FuzzyDate.
 *
 * @property FuzzyDate $value Date associée.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TypedFuzzyDate extends TypedValue
{
    public static function loadSchema()
    {
        return [
            'label' => __('Date', 'docalist-core'),
            'description' => __('Date et type de date.', 'docalist-core'),
            'fields' => [
                'value' => [
                    'type' => FuzzyDate::class,
                    'label' => __('Date', 'docalist-core'),
                    'description' => __('Date au format AAAAMMJJ', 'docalist-core'),
                ]
            ]
        ];
    }
}
