<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
declare(strict_types=1);

namespace Docalist\Views\Forms\Base;

use Docalist\Forms\HtmlBlock;
use Docalist\Forms\Theme;

/**
 * @var HtmlBlock   $this  Le bloc Html à afficher.
 * @var Theme       $theme Le thème de formulaire en cours.
 * @var array       $args  Paramètres transmis à la vue.
 */
$theme->html($this->getContent());
