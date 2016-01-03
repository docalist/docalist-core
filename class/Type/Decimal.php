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
 * Type nombre décimal.
 */
class Decimal extends Number
{
    public function assign($value)
    {
        ($value instanceof Any) && $value = $value->value();
        if (! is_float($value)) {
            if (false === $value = filter_var($value, FILTER_VALIDATE_FLOAT)) {
                throw new InvalidTypeException('float');
            }
        }

        $this->value = $value;

        return $this;
    }

    public function getEditorForm($options = null)
    {
        return parent::getEditorForm($options)->setAttribute('step', '0.01');
    }
}
