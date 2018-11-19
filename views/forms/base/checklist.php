<?php declare(strict_types=1);
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
namespace Docalist\Views\Forms;

use Docalist\Forms\Checklist;
use Docalist\Forms\Theme;

/**
 * @var Checklist $this  L'élément de formulaire à afficher.
 * @var Theme     $theme Le thème de formulaire en cours.
 * @var array     $args  Paramètres transmis à la vue.
 */
$theme->display($this, 'radiolist'); // Même "renderer" pour radiolist et checklist
