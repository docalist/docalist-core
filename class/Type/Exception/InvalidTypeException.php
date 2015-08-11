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
 */
namespace Docalist\Type\Exception;

use InvalidArgumentException;

/**
 * Exception générée lorsqu'un nom de type est incorrect.
 */
class InvalidTypeException extends InvalidArgumentException {
    /**
     * Construit l'exception.
     *
     * @param string $expected Chaine décrivant le type qui était attendu.
     */
    public function __construct($expected) {
//        $msg = __('Type incorrect, "%s" attendu', 'docalist-core');
        $msg = 'Type incorrect, "%s" attendu';
        $msg = sprintf($msg, $expected);
        parent::__construct($msg);
    }
}