<?php
/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Views
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Views\Forms\Base;

use Docalist\Forms\Container;
use Docalist\Forms\Theme;

/**
 * @var Container $this  Le container à afficher.
 * @var Theme     $theme Le thème de formulaire en cours.
 * @var array     $args  Paramètres transmis à la vue.
 */
foreach(array_keys($this->getOccurences()) as $key) {
    $this->setOccurence($key);
    $theme->display($this, 'container-items');
}
$this->isRepeatable() && $theme->display($this, '_add');