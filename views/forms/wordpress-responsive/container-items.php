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

use Docalist\Forms\Container;
use Docalist\Forms\Theme;

/**
 *  @var $this  Container Le container à afficher.
 *  @var $theme Theme     Le thème de formulaire en cours.
 *  @var $args  array     Paramètres transmis à la vue.
 */
//$theme->start('table', ['border'=>1, 'class' => 'form-table']);
foreach($this->getItems() as $item) {
    $theme->start('div', ['class' => 'df-row block-group']);

    $theme
        ->start('div', ['class' => 'df-label block df-label-' . $item->getType()])
        ->display($item, '_label')
        ->end('div');

    $theme
        ->start('div', ['class' => 'df-item block df-item-' . $item->getType()])
        ->display($item)
        ->display($item, '_description')
        ->end('div');

    $theme->end('div');
}

//$theme->end('table');
