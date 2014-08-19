<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Docalist\Type;

use Docalist\Type\Exception\InvalidTypeException;

/**
 * Type entier.
 */
class Integer extends Scalar {
    static protected $default = 0;

    public function assign($value) {
        ($value instanceof Any) && $value = $value->value();
        if (! is_int($value)){
            throw new InvalidTypeException('int');
        }

        $this->value = $value;

        return $this;
    }
}