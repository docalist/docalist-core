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

namespace Docalist\Type\Exception;

use InvalidArgumentException;

/**
 * Exception générée lorsqu'un nom de type est incorrect.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class InvalidTypeException extends InvalidArgumentException
{
    /**
     * Construit l'exception.
     *
     * @param string $expected le nom du type qui était attendu.
     */
    public function __construct($expected)
    {
        parent::__construct(sprintf('Incorrect type, expected %s', $expected));
    }
}
