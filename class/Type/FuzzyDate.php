<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Type;

use InvalidArgumentException;

/**
 * Une date éventuellement incomplète : 'yyyyMMdd', 'yyyyMM' ou 'yyyy'.
 */
class FuzzyDate extends Text
{
    // format affichage => format obtenu si on a ['année seulement', 'année et mois', 'année, mois et jour]
    static protected $map = [
        'Y'     => ['Y',    'Y',    'Y'     ],

        'Y/m'   => ['Y',    'Y/m',  'Y/m'   ],
        'm/Y'   => ['Y',    'm/Y',  'm/Y'   ],
        'Y-m'   => ['Y',    'Y-m',  'Y-m'   ],
        'm-Y'   => ['Y',    'm-Y',  'm-Y'   ],

        'd/m/Y' => ['Y',    'm/Y',  'd/m/Y' ],
        'd-m-Y' => ['Y',    'm-Y',  'd-m-Y' ],
        'Y/m/d' => ['Y',    'Y/m',  'Y/m/d' ],
        'Y-m-d' => ['Y',    'Y-m',  'Y-m-d' ],
    ];

    public function getAvailableFormats()
    {
        return [
            'Y'     => __('AAAA', 'docalist-core'),

            'Y/m'   => __('AAAA/MM', 'docalist-core'),
            'm/Y'   => __('MM/AAAA', 'docalist-core'),
            'Y-m'   => __('AAAA-MM', 'docalist-core'),
            'm-Y'   => __('MM-AAAA', 'docalist-core'),

            'd/m/Y' => __('JJ/MM/AAAA', 'docalist-core'),
            'd-m-Y' => __('JJ-MM-AAAA', 'docalist-core'),
            'Y/m/d' => __('AAAA/MM/JJ', 'docalist-core'),
            'Y-m-d' => __('AAAA-MM-JJ', 'docalist-core'),
        ];
    }

    public function getDefaultFormat()
    {
        return 'd/m/Y';
    }

    /**
     * Retourne les différentes parties (année, mois, jour) qui composent la date.
     *
     * @return array Retourne un tableau de 1, 2 ou 3 éléments.
     */
    protected function parse() {
        $date = $this->value;
        $parts = [];

        $parts[0] = substr($date, 0, 4); // year
        if (strlen($date) <= 4) {
            return $parts;
        }

        $parts[1] = substr($date, 4, 2); // month
        if (strlen($date) <= 6) {
            return $parts;
        }

        $parts[2] = substr($date, 6, 2); // day

        return $parts;
    }

    public function getFormattedValue($options = null)
    {
        // Valide le format demandé
        $format = $this->getOption('format', $options, $this->getDefaultFormat());
        if (! isset(self::$map[$format])) {
            throw new InvalidArgumentException("Invalid FuzzyDate format '$format'");
        }

        // Découpe la date
        $parts = $this->parse(); // 0 = vide, 1=que l'année, 2=année et mois, 3=année, mois et jour

        // Détermine le format effectif en fonction de la précision de la date
        $format = self::$map[$format];
        $format = $format[count($parts) - 1];

        // Formatte la date
        return strtr($format, [
            'Y' => isset($parts[0]) ? $parts[0] : '????',
            'm' => isset($parts[1]) ? $parts[1] : '??',
            'd' => isset($parts[2]) ? $parts[2] : '??',
        ]);
    }
}
