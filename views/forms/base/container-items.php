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

use Docalist\Forms\Container;
use Docalist\Forms\Theme;
use Docalist\Forms\Element;
use Docalist\Forms\TopicsInput;

/**
 * @var Container $this  Le container à afficher.
 * @var Theme     $theme Le thème de formulaire en cours.
 * @var array     $args  Paramètres transmis à la vue.
 */
$inTable = false;
foreach ($this->getItems() as $item) {
    if ($item->hasLayout()) {
        if (!$inTable) {
//             $attr = $this->getAttributes();
//             $attr['class'] = isset($attr['class']) ? ('form-table ' . $attr['class']) : 'form-table';
//             $theme->start('table', $attr);

            $theme->start('table', ['class' => 'form-table']);

            $inTable = true;
        }
    } else {
        if ($inTable) {
            $theme->end('table');
            $inTable = false;
        }
        $theme->display($item);
        $item->hasDescriptionBlock() && $theme->display($item, '_description');
        continue;
    }

    $attr = [];
    if ($item instanceof Element) { /** @var Element $item */
        $name = $item->getName();
        !empty($name) && $attr['class'] = 'field-' . $name . '-group';
        $required = $item->getRequired();
        !empty($required) && $attr['data-required'] = $required;
    }
    $theme->start('tr', $attr);

    $theme->start('th');
    $item->hasLabelBlock() && $theme->display($item, '_label');
    $theme->end('th');
    // Remarque : pas d'attribut scope="row"
    // For simple tables that have the headers in the first row or column then
    // it is sufficient to simply use the TH elements without scope.
    // source : http://www.w3.org/TR/WCAG20-TECHS/H63.html

    $container = ($item instanceof Container) || ($item instanceof TopicsInput);
    $class = $container ? ['class' => $item->getAttribute('class')] : [];
    $theme->start('td', $class);
//    $theme->text('container ? ' . var_export($container, true) . ', type=' . get_class($item));
    $container && $theme->display($item, '_description');
    $theme->display($item);
    !$container && $item->hasDescriptionBlock() && $theme->display($item, '_description');
    $theme->end('td');

    $theme->end('tr');
}

$inTable && $theme->end('table');
