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

namespace Docalist\Repository;

use Docalist\Type\Entity;

/**
 * Un dépôt permettant de stocker des entités dans le répertoire "config" de l'application.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ConfigRepository extends DirectoryRepository
{
    /**
     * Crée un nouveau dépôt.
     *
     * @param string $type Optionnel, le nom de classe complet des entités de
     * ce dépôt. C'est le type qui sera utilisé par load() si aucun type
     * n'est indiqué lors de l'appel.
     */
    public function __construct($type = Entity::class)
    {
        parent::__construct(docalist('config-dir'), $type);
    }
}
