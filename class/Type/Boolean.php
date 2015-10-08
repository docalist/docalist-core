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
 * Type booléen.
 */
class Boolean extends Scalar
{
    public static function getClassDefault()
    {
        return true;
    }

    public function assign($value)
    {
        ($value instanceof Any) && $value = $value->value();
        if (! is_bool($value)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (is_null($value)) {
                throw new InvalidTypeException('boolean');
            }
        }

        $this->value = $value;

        return $this;
    }

    public function getEditorForm(array $options = null)
    {
        return parent::getEditorForm($options)->attribute('type', 'checkbox');
    }
}
