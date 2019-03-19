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

use Docalist\Type\Exception\InvalidTypeException;

/**
 * Un nombre.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Number extends Scalar
{
    public static function getClassDefault()
    {
        return 0;
    }

    public function assign($value): void
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (! is_numeric($value)) {
            if (false === $value = filter_var($value, FILTER_VALIDATE_FLOAT)) {
                throw new InvalidTypeException('number');
            }
        }

        $this->phpValue = $value;
    }

    public function getEditorForm($options = null)
    {
        return parent::getEditorForm($options)->setAttribute('type', 'number');
    }
}
