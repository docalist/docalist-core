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
