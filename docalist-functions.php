<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
declare(strict_types=1);

namespace Docalist;

/**
 * Génère un warning de type E_USER_DEPRECATED pour une méthode ou une classe qu'il ne faut plus utiliser.
 *
 * @param string $deprecated    Nom de la classe ou de la méthode dépréciée.
 * @param string $replacement   Optionnel, classe ou méthode à utiliser à la place.
 * @param string $since         Optionnel, version ou date de dépreciation.
 */
function deprecated(string $deprecated, string $replacement = '', string $since = ''): void
{
    $message = sprintf('[DEPRECATED   ] %s is deprecated', $deprecated);
    !empty($since) && $message .= sprintf(' since %s', $since);
    !empty($replacement) && $message .= sprintf(', use %s instead', $replacement);

    $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
    // 0 = la fonction deprecated actuelle
    // 1 = le container
    // 2 = la méthode qui a appellé le container
    $trace = $stack[2];

    // @phpstan-ignore-next-line
    $file = $trace['file'];
    $root = dirname(__DIR__, 2);
    if (str_starts_with($file, $root)) {
        $file = substr($file, strlen($root) + 1);
    }

    // @phpstan-ignore-next-line
    $line = $trace['line'];

    $message .= sprintf(' in %s:%d', $file, $line);

    error_log($message);
}
