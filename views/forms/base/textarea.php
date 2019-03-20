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
namespace Docalist\Views\Forms\Base;

use Docalist\Forms\Textarea;
use Docalist\Forms\Theme;

/**
 * @var Textarea $this  L'élément de formulaire à afficher.
 * @var Theme    $theme Le thème de formulaire en cours.
 * @var array    $args  Paramètres transmis à la vue.
 */

// Envoie le js autosize
$this->hasClass('autosize') && $theme->enqueueScript('docalist-textarea-autosize');

foreach ($this->getOccurences() as $key => $data) {
    $this->setOccurence($key);
    $attributes = ['name' => $this->getControlName()] + $this->getAttributes();
    $theme
        ->start('textarea', $attributes)
        ->text($data)
        ->end('textarea');
}
$this->isRepeatable() && $theme->display($this, '_add');
