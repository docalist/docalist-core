<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Views\Forms\Base;

use Docalist\Forms\Div;
use Docalist\Forms\Theme;

/**
 * @var Div     $this  Le container à afficher.
 * @var Theme   $theme Le thème de formulaire en cours.
 * @var array   $args  Paramètres transmis à la vue.
 */

foreach (array_keys($this->getOccurences()) as $key) {
    $theme->start('div', $this->getAttributes());
    $this->setOccurence($key);

    foreach ($this->getItems() as $item) {
        $theme->display($item);
    }

    $theme->end('div');
}
$this->isRepeatable() && $theme->display($this, '_add');
