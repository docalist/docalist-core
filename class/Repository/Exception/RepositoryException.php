<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Repository\Exception;

use RuntimeException;

/**
 * Exception générée lorsqu'une erreur survient dans un dépôt.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class RepositoryException extends RuntimeException
{
    /**
     * Construit l'exception.
     *
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
