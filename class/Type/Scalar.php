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

use Docalist\Type\Exception\InvalidTypeException;

/**
 * Classe de base pour les types scalaires.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Scalar extends Any
{
    public static function getClassDefault()
    {
        return '';
    }

    public function assign($value): void
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (! is_scalar($value)) {
            throw new InvalidTypeException('scalar');
        }

        $this->phpValue = $value;
    }

    public function getFormattedValue($options = null)
    {
        return (string) $this->phpValue;
    }

    public function filterEmpty(bool $strict = true): bool
    {
        return false;
    }
}
