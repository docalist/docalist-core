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

use Docalist\Type\DateTime;

/**
 * Une période composée d'un DateTime de début et de fin.
 *
 * Exemple : "2016-01-19 09:00","2016-01-20 17:00"
 *
 * @property DateTime $start    Date/heure de début
 * @property DateTime $end      Date/heure de fin
 */
class DateTimeInterval extends Composite
{
    public static function loadSchema()
    {
        return [
            'fields' => [
                'start' => [
                    'type' => 'Docalist\Type\DateTime',
                    'label' => __('Début', 'docalist-biblio-export'),
                    'description' => __("Date / heure de début", 'docalist-biblio-export'),
                ],
                'end' => [
                    'type' => 'Docalist\Type\DateTime',
                    'label' => __('Fin', 'docalist-biblio-export'),
                    'description' => __("Date / heure de fin", 'docalist-biblio-export'),
                ],
            ],
        ];
    }
}
