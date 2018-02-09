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

use Docalist\Forms\Input;
use Docalist\Forms\Theme;

/**
 * @var Input $this  L'élément de formulaire à afficher.
 * @var Theme $theme Le thème de formulaire en cours.
 * @var array $args  Paramètres transmis à la vue.
 */
foreach ($this->getOccurences() as $key => $data) {
    $this->setOccurence($key);
    $attributes = ['name' => $this->getControlName(), 'value' => $data] + $this->getAttributes();
    $theme->tag('input', $attributes);
}
$this->isRepeatable() && $theme->display($this, '_add');
