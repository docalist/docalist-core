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

use Docalist\Forms\Form;
use Docalist\Forms\Theme;

/**
 * @var Form  $this  Le formulaire à afficher.
 * @var Theme $theme Le thème de formulaire en cours.
 * @var array $args  Paramètres transmis à la vue.
 */

$this->hasDescriptionBlock() && $theme->display($this, '_description');

$theme
    ->start('form', $this->getAttributes())
    ->display($this, 'container')
    ->end('form');
