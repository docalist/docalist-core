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

use Docalist\Forms\Metabox;
use Docalist\Forms\Theme;

/**
 * @var Metabox $this  Le container à afficher.
 * @var Theme   $theme Le thème de formulaire en cours.
 * @var array   $args  Paramètres transmis à la vue.
 */
foreach (array_keys($this->getOccurences()) as $key) {
    $this->setOccurence($key);

    $attributes = $this->getAttributes();
    if (isset($attributes['class'])) {
        $attributes['class'] .= ' postbox';
    } else {
        $attributes['class'] = ' postbox';
    }
    $theme->start('div', $attributes);
        $theme->start('div', ['class' => 'postbox-header']);

            $theme->start('h2', ['class' => 'hndle']);
                $theme->tag('span', [], $this->getLabel() ?: $this->getName());
            $theme->end('h2');
/*

            $theme->start('button', ['type' => 'button', 'class' => 'handle-order-higher']);
                $theme->tag('span', ['class' => 'order-higher-indicator']);
            $theme->end('button');

            $theme->start('button', ['type' => 'button', 'class' => 'handle-order-lower']);
                $theme->tag('span', ['class' => 'order-lower-indicator']);
            $theme->end('button');
*/
            $theme->start('div', ['class' => 'handle-actions hide-if-no-js']);
                $theme->start('button', ['type' => 'button', 'class' => 'handlediv button-link', 'aria-expanded' => 'true']);
                    $theme->tag('span', ['class' => 'toggle-indicator']);
                $theme->end('button');
            $theme->end('div'); // div.handle-actions

        $theme->end('div'); // div.postbox-header

        $theme->start('div', ['class' => 'inside']);
            if ($description = $this->getDescription()) {
                $theme->tag('p', ['class' => 'description'], $description);
            }
            $theme->display($this, 'container-items');
        $theme->end('div'); // div.inside


    $theme->end('div'); // div.postbox
}
$this->isRepeatable() && $theme->display($this, '_add');
