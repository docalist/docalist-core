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
 * Classe de base pour les types scalaires.
 */
class Scalar extends Any {
    static protected $default = ''; // null ? false ?

    public function assign($value) {
        ($value instanceof Any) && $value = $value->value();
        if (! is_scalar($value)){
            throw new InvalidTypeException('scalar');
        }

        $this->value = $value;

        return $this;
    }

    public function __toString() {
        return (string) $this->value;
    }
}