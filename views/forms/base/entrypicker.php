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

use Docalist\Forms\EntryPicker;
use Docalist\Forms\Theme;

/**
 * @var EntryPicker $this  Le champ à afficher.
 * @var Theme       $theme Le thème de formulaire en cours.
 * @var array       $args  Paramètres transmis à la vue.
 */
// Envoie le js et la css de selectize
$theme->enqueueStyle('selectize')->enqueueScript('selectize');

// Affiche les select multiple sur une seule ligne tant qu'ils n'ont pas été initialisés (pour limiter FOUC)
$this->hasAttribute('multiple') && $this->setAttribute('size', 1);

// Ajoute les attributs dont on a besoin pour gérer les lookups
$options = $this->getOptions();
if (is_string($options)) {
    list($type, $source) = explode(':', $options, 2);
    $this->setAttribute('data-lookup-type', $type);
    $this->setAttribute('data-lookup-source', $source);
}

// Affiche le contrôle comme un select standard (même "renderer" que pour select)
$theme->display($this, 'select');
