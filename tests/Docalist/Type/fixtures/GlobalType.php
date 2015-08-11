<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */

use Docalist\Type\Any;

/**
 * Classe globale (pas de namespace) utilisée pour tester className() et ns()
 * dans AnyTest.php.
 *
 * Comme cette classe n'a pas de namespace, elle ne peut pas être chargée
 * automatiquement par l'autoloader, donc il faut inclure le fichier
 * manuellement.
 */
class GlobalType extends Any {
}