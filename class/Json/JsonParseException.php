<?php
/**
 * This file is part of the 'Docalist Biblio' plugin.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Json;

use RuntimeException;

/**
 * Exception générée par JsonReader lorsqu'une erreur est détectée dans le fichier JSON.
 */
class JsonParseException extends RuntimeException
{
    public function __construct ($message, $line = 0, $col = 0)
    {
        parent::__construct(sprintf('JSON error line %d, column %d: %s.', $line, $col, $message));
    }
}
