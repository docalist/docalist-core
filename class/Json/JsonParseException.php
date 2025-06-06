<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Json;

use RuntimeException;

/**
 * Exception générée par JsonReader lorsqu'une erreur est détectée dans le fichier JSON.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class JsonParseException extends RuntimeException
{
    public function __construct(string $message, int $line = 0, int $col = 0)
    {
        parent::__construct(sprintf('JSON error line %d, column %d: %s.', $line, $col, $message));
    }
}
