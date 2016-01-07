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

use Docalist\Forms\Button;
use Docalist\Forms\Theme;

/**
 * @var Button $this  L'élément de formulaire à afficher.
 * @var Theme  $theme Le thème de formulaire en cours.
 * @var array  $args  Paramètres transmis à la vue.
 */
foreach($this->getOccurences() as $key => $data) {
    $this->setOccurence($key);
    $attributes = ['name' => $this->getControlName(), 'value' => $data] + $this->getAttributes();
    $theme->tag('button', $attributes, $this->getLabel());
}
$this->isRepeatable() && $theme->display($this, '_add');
