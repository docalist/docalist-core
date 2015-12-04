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

use Docalist\Forms\Element;
use Docalist\Forms\Theme;

/**
 * @var Element $this  L'élément de formulaire à afficher.
 * @var Theme   $theme Le thème de formulaire en cours.
 * @var array   $args  Paramètres transmis à la vue.
 */

if (! $this->hasDescriptionBlock()) {
    $theme->text('View _description: ' . ($this->getName() ?: $this->getType()) . '::hasDescriptionBlock is false.');
    return;
}

$description = $this->getDescription();
if (empty($description) || $description === '-') {
    return;
}

$theme->tag('p', ['class' => 'description'], $description);
