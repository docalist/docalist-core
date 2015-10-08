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

use Docalist\Type\Exception\InvalidTypeException;

/**
 * Un nombre.
 */
class Number extends Scalar
{
    public static function getClassDefault()
    {
        return 0;
    }

    public function assign($value)
    {
        ($value instanceof Any) && $value = $value->value();
        if (! is_numeric($value)) {
            if (false === $value = filter_var($value, FILTER_VALIDATE_FLOAT)) {
                throw new InvalidTypeException('number');
            }
        }

        $this->value = $value;

        return $this;
    }

    public function getEditorForm(array $options = null)
    {
        return parent::getEditorForm($options)->attribute('type', 'number');
    }
}
