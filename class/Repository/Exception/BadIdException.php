<?php
/**
 * This file is part of the "Docalist Core" plugin.
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
namespace Docalist\Repository\Exception;

/**
 * Exception générée lorsque l'identifiant d'une entité est invalide.
 */
class BadIdException extends RepositoryException
{
    /**
     * Construit l'exception.
     *
     * @param mixed $id
     * @param string $expected Le type attendu.
     */
    public function __construct($id, $expected)
    {
        if (is_null($id)) {
            $msg = __('Entity ID is required (got null)', 'docalist-core');
        } else {
            $id = is_scalar($id) ? (string) $id : gettype($id);
            $msg = __('Invalid entity ID "%s", expected %s', 'docalist-core');
        }
        parent::__construct(sprintf($msg, $id, $expected));
    }
}
