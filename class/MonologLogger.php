<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist;

use Monolog\Logger;
use DateTimeZone;

/**
 * Wrapper pour Monolog\Logger.
 *
 * Cette classe ne devrait pas exister : elle sert juste à définir la timezone
 * correcte pour monolog.
 *
 * Le problème, c'est que wordpress fixe en dur la timezone à UTC, sans tenir
 * compte de ce qu'on a pu mettre dans php.ini pour la clé date.timezone :
 *
 * wp-settings.php:43 date_default_timezone_set('UTC')
 *
 * Du coup, toutes les fonctions date de php retournent une date UTC et pour
 * avoir la "bonne" date, on est obligé de passer par les fonctions wordpress.
 *
 * Tant qu'on est dans le monde WordPress, c'est possible, mais si on utilise
 * une librairie externe correctement écrite qui utilise la fonction
 * date_default_timezone_get, on est coincé.
 *
 * Cette classe se contente d'indiquer à Monolog la bonne timezone (celle qui
 * figure dans php.ini) lorsque le premier logger est créé (on ne peut pas le
 * faire de l'extérieur car la variable statique Monolog\Logger::$timezone est
 * protected.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class MonologLogger extends Logger
{
    public function __construct($name, array $handlers = [], array $processors = [])
    {
        if (!static::$timezone) {
            $timezone = ini_get('date.timezone');
            $timezone && static::$timezone = new DateTimeZone($timezone);
        }

        parent::__construct($name, $handlers, $processors);
    }
}
