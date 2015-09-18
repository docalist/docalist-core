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
 * Type chaine de caractères.
 */
class Text extends Scalar
{

    public static function classDefault()
    {
        return '';
    }

    public function assign($value)
    {
        ($value instanceof Any) && $value = $value->value();
        if (! is_string($value)) {
            if (! is_scalar($value)) {
                throw new InvalidTypeException('string');
            }
            $value = (string) $value;
        }

        $this->value = $value;

        return $this;
    }
}
