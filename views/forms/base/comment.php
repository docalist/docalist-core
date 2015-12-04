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

use Docalist\Forms\Comment;
use Docalist\Forms\Theme;

/**
 * @var Comment $this  Le commentaire à afficher.
 * @var Theme   $theme Le thème de formulaire en cours.
 * @var array   $args  Paramètres transmis à la vue.
 */
$theme->comment($this->getContent());
