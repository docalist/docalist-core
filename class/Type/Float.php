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
 * Type nombre décimal.
 */
class Float extends Scalar {
    static protected $default = 0.0;

    public function assign($value) {
        if (! is_float($value)){
            if (is_int($value)) {
                $value = (float) $value;
            } else {
                throw new InvalidTypeException('float');
            }
        }

        $this->value = $value;

        return $this;
    }
}