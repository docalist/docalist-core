<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Core
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Repository;

/**
 * Un dépôt permettant de stocker des entités dans le répertoire
 * "config" de l'application.
 */
class ConfigRepository extends DirectoryRepository {
    /**
     * Crée un nouveau dépôt.
     */
    public function __construct() {
        parent::__construct(docalist('site-root') . 'config');
    }
}