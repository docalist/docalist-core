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

use Docalist\Forms\Metabox;
use Docalist\Forms\Theme;

/**
 * @var Metabox $this  Le container à afficher.
 * @var Theme   $theme Le thème de formulaire en cours.
 * @var array   $args  Paramètres transmis à la vue.
 */
foreach(array_keys($this->getOccurences()) as $key) {
    $this->setOccurence($key);

    $attributes = $this->getAttributes();
    if (isset($attributes['class'])) {
        $attributes['class'] .= ' postbox';
    } else {
        $attributes['class'] = ' postbox';
    }
    $theme->start('div', $attributes);

        $theme->start('button', ['type' => 'button', 'class' => 'handlediv button-link', 'aria-expanded' => 'true']);
            $theme->tag('span', ['class' => 'toggle-indicator']);
        $theme->end('button');

        $theme->start('h2', ['class' => 'hndle']);
            $theme->tag('span', [], $this->getLabel() ?: $this->getName());
//            $theme->tag('span', ['class' => 'description', 'style' => 'float:right;opacity: .5'], $this->getLabel() ?: $this->getDescription());
        $theme->end('h2');

        $theme->start('div', ['class' => 'inside']);
            if ($description = $this->getDescription()) {
                $theme->tag('p', ['class' => 'description'], $description);
            }

            $theme->display($this, 'container-items');
        $theme->end('div'); // div.inside

    $theme->end('div'); // div.postbox
}
$this->isRepeatable() && $theme->display($this, '_add');