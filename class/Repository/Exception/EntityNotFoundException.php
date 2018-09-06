<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Repository\Exception;

/**
 * Exception générée lorsqu'une entité ne peut pas être chargée.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class EntityNotFoundException extends RepositoryException
{
    /**
     * Construit l'exception.
     *
     * @param int|string $id Identifiant de l'entité..
     */
    public function __construct($id)
    {
        $msg = __('Entity %s not found', 'docalist-core');
        parent::__construct(sprintf($msg, $id));
    }
}
