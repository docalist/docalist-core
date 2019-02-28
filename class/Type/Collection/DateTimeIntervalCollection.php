<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type\Collection;

use Docalist\Type\Collection;
use Docalist\Type\DateTimeInterval;
use DateTime;

/**
 * Une collection d'objets DateTimeInterval.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class DateTimeIntervalCollection extends Collection
{
    /**
     * Retourne la plus petite des dates de début des intervalles de dates présents dans la collection.
     *
     * @return DateTime|null
     */
    public function getStartDate(): ?DateTime
    {
        $start = null;
        foreach ($this->phpValue as $interval) { /** @var DateTimeInterval $interval */
            $date = $interval->start->getPhpValue();
            if (empty($date)) {
                continue;
            }
            if (is_null($start) || ($date < $start)) {
                $start = $date;
            }
        }

        return is_null($start) ? null : new DateTime($start);
    }

    /**
     * Retourne la plus petite des dates de fin des intervalles de dates présents dans la collection.
     *
     * @return DateTime|null
     */
    public function getEndDate(): ?DateTime
    {
        $end = null;
        foreach ($this->phpValue as $interval) { /** @var DateTimeInterval $interval */
            $date = $interval->end->getPhpValue();
            if (empty($date)) {
                $date = $interval->start->getPhpValue();
                if (empty($date)) {
                    continue;
                }
            }
            if (is_null($end) || ($date > $end)) {
                $end = $date;
            }
        }

        return is_null($end) ? null : new DateTime($end);
    }
}
