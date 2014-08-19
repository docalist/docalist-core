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
namespace Docalist\Repository\Exception;

use RuntimeException ;

/**
 * Exception générée lorsqu'une erreur survient dans un dépôt.
 */
class RepositoryException extends RuntimeException {
    /**
     * Construit l'exception.
     *
     * @param string $message
     */
    public function __construct($message) {
        parent::__construct($message);
    }
}