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

use Docalist\Forms\Text;
use Docalist\Forms\Theme;

/**
 * @var Text  $this  Le bloc de texte à afficher.
 * @var Theme $theme Le thème de formulaire en cours.
 * @var array $args  Paramètres transmis à la vue.
 */
$theme->text($this->getContent());
