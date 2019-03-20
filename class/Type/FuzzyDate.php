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

use Docalist\Forms\Element;
use InvalidArgumentException;

/**
 * Une date éventuellement incomplète : 'yyyyMMdd', 'yyyyMM' ou 'yyyy'.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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

    public function getAvailableFormats(): array
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

    public function getDefaultFormat(): string
    {
        return 'd/m/Y';
    }

    /**
     * Retourne les différentes parties (année, mois, jour) qui composent la date.
     *
     * @return array Retourne un tableau de 1, 2 ou 3 éléments.
     */
    protected function parse(): array
    {
        $date = $this->phpValue;
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

    public function getEditorForm($options = null): Element
    {
        return parent::getEditorForm($options)
            ->setAttribute('pattern', $this->getValidationPattern());
    }

    /**
     * Construit une expression régulière permettant de valider une fuzzy date.
     *
     * L'expression régulière générée accepte des dates au format AAAA, AAAAMM ou AAAAMMJJ qui respectent les
     * contraintes suivantes :
     *
     * - l'année doit être comprise entre 1900 et 2099,
     * - le mois de février peut avoir maximum 29 jours (on ne gère pas les années bisextiles),
     * - les mois d'avril, juin, septembre et novembre peuvet avoir maximum 30 jours,
     * - les autres mois peuvent avoir 31 jours.
     *
     * Remarque : le pattern généré n'a pas de délimiteurs de début et de fin (/xxx/) ni d'ancres ('^' et '$').
     *
     * @param string $separator Par défaut, les dates reconnues n'ont aucun séparateur entre l'année, le mois et le
     * jour. Pour valider des dates contenant un séparateur, vous pouvez indiquer un caractère unique (par exemple '-'
     * pour des dates au format AAAA-MM-JJ) ou un pattern indiquant les séparateurs autorisés (par exemple '[-/]'
     * pour accepter des dates au format AAAA-MM-JJ ou AAAA/MM/JJ).
     *
     * @return string L'expression régulière générée.
     */
    protected function getValidationPattern(string $separator = ''): string
    {
        $monthday = $day = function ($pattern) use ($separator) {
            return $separator ? "(?:$separator(?:$pattern))?" : "(?:$pattern)?";
        };

        return '' .
            // Année entre 1900 et 2099
            '(?:19|20)\d{2}' .

            // Mois et jour optionnels
            $monthday(
                '(?:0[13578]|1[02])'    . $day('0[1-9]|[12][0-9]|3[01]') .  // Mois de 31 jours
                '|(?:0[469]|11)'        . $day('0[1-9]|[12][0-9]|30') .     // Mois de 30 jours
                '|02'                   . $day('0[1-9]|[12][0-9]')          // Mois de février : maxi 29 jours
            );
    }
/*
    // Test de la méthode getValidationPattern(), à transférer dans test phpunit
    public function test(array $tests = null)
    {
        is_null($tests) && $tests = [
            '1900',
            '1999',
            '2000',
            '2099',
            '2017',
            '2017-01',
            '2017-01-01',
            '2017-01-29',
            '2017-01-30',
            '2017-01-31',
            '2017-02',
            '2017-02-01',
            '2017-02-29',
            '2017-03',
            '2017-03-01',
            '2017-03-29',
            '2017-03-30',
            '2017-03-31',
            '2017-04',
            '2017-04-01',
            '2017-04-29',
            '2017-04-30',
            '2017-05',
            '2017-05-01',
            '2017-05-29',
            '2017-05-30',
            '2017-05-31',
            '2017-06',
            '2017-05-01',
            '2017-06-29',
            '2017-06-30',
            '2017-07',
            '2017-07-01',
            '2017-07-29',
            '2017-07-30',
            '2017-07-31',
            '2017-08',
            '2017-08-01',
            '2017-08-29',
            '2017-08-30',
            '2017-08-31',
            '2017-09',
            '2017-09-01',
            '2017-09-29',
            '2017-09-30',
            '2017-10',
            '2017-10-01',
            '2017-10-29',
            '2017-10-30',
            '2017-10-31',
            '2017-11',
            '2017-11-01',
            '2017-11-29',
            '2017-11-30',
            '2017-12',
            '2017-12-01',
            '2017-12-29',
            '2017-12-30',
            '2017-12-31',

            '1860',
            '2017-',
            '2017-00',
            '2017-1',
            '2017-01-00',
            '2017-02-30',
            '2017-02-31',
            '2017-04-31',
            '2017-06-31',
            '2017-09-31',
            '2017-11-31',
            '2017-13',
            '2017-13-01',
            '2100',
        ];

        $sep = '';

        $re = '~^' . $this->getValidationPattern($sep) . '$~';
        $matches = null;
        echo '<pre>';
        echo $re, '<br />';
        foreach ($tests as $test) {
            $test = str_replace('-', $sep, $test);
            $ok = preg_match($re, $test, $matches);
//            echo $test, ' ', ($ok ? 'ok' : 'erreur'), var_export($matches, true), '<br />';
            echo ($ok ? 'ok...: ' : 'ERR..: '), $test, '<br />';
        }
    }
*/
}
