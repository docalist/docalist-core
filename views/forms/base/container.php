<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Views\Forms\Base;

use Docalist\Forms\Container;
use Docalist\Forms\Theme;

/**
 * @var Container $this  Le container à afficher.
 * @var Theme     $theme Le thème de formulaire en cours.
 * @var array     $args  Paramètres transmis à la vue.
 */
$this->hasDescriptionBlock() && $theme->display($this, '_description');
foreach (array_keys($this->getOccurences()) as $key) {
    $this->setOccurence($key);
    $theme->display($this, 'container-items');
}
$this->isRepeatable() && $theme->display($this, '_add');
