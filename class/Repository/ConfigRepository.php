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
 * @version     SVN: $Id$
 */
namespace Docalist\Repository;

/**
 * Un dépôt permettant de stocker des entités dans le répertoire
 * "config" de l'application.
 */
class ConfigRepository extends DirectoryRepository {
    /**
     * Crée un nouveau dépôt.
     *
     * @param string $type Optionnel, le nom de classe complet des entités de
     * ce dépôt. C'est le type qui sera utilisé par load() si aucun type
     * n'est indiqué lors de l'appel.
     */
    public function __construct($type = 'Docalist\Type\Entity') {
        parent::__construct(docalist('config-dir'), $type);
    }
}