<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Repository\Exception;

/**
 * Exception générée lorsque l'identifiant d'une entité est invalide.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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
