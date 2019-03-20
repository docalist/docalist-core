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

use Docalist\Forms\CodeEditor;
use Docalist\Forms\Theme;

/**
 * @var CodeEditor  $this  L'élément de formulaire à afficher.
 * @var Theme       $theme Le thème de formulaire en cours.
 * @var array       $args  Paramètres transmis à la vue.
 */
foreach ($this->getOccurences() as $key => $data) {
    $this->setOccurence($key);

    // La textarea doit obligatoirement avoir un ID pour qu'on l'initialise avec Code Mirror
    $id = $this->generateId();

    // Génère la textarea
    $attributes = ['name' => $this->getControlName()] + $this->getAttributes();
    $theme
        ->start('textarea', $attributes)
        ->text($data)
        ->end('textarea');

    // Demande à WordPress de générer les assets et les options poru CodeMirror
    $settings = wp_enqueue_code_editor($this->getOptions());

    // L'utilisateur peut désactiver Code Mirror dans son profil. Si c'est le cas, on génère une textarea standard
    if (false === $settings) {
        $this->hasClass('autosize') && $theme->enqueueScript('docalist-textarea-autosize');
    }

    // Code Mirror n'est pas désactivé, initialise la textbox
    wp_add_inline_script('code-editor', sprintf('jQuery(function(){wp.codeEditor.initialize("%s")});', $id));
}
$this->isRepeatable() && $theme->display($this, '_add');
