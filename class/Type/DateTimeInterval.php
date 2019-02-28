<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Type\DateTime;
use Docalist\Type\Collection\DateTimeIntervalCollection;

/**
 * Une période composée d'un DateTime de début et de fin.
 *
 * Exemple : "2016-01-19 09:00","2016-01-20 17:00"
 *
 * @property DateTime $start    Date/heure de début
 * @property DateTime $end      Date/heure de fin
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class DateTimeInterval extends Composite
{
    /**
     * {@inheritDoc}
     */
    public static function loadSchema()
    {
        return [
            'fields' => [
                'start' => [
                    'type' => DateTime::class,
                    'label' => __('Début', 'docalist-core'),
                    'description' => __("Date / heure de début", 'docalist-core'),
                ],
                'end' => [
                    'type' => DateTime::class,
                    'label' => __('Fin', 'docalist-core'),
                    'description' => __("Date / heure de fin", 'docalist-core'),
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function getCollectionClass()
    {
        return DateTimeIntervalCollection::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultEditor()
    {
        return 'table';
    }
}
