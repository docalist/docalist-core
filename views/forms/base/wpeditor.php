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

use Docalist\Forms\WPEditor;
use Docalist\Forms\Theme;

/**
 * @var WPEditor $this  L'élément de formulaire à afficher.
 * @var Theme    $theme Le thème de formulaire en cours.
 * @var array    $args  Paramètres transmis à la vue.
 */
foreach($this->getOccurences() as $key => $data) {
    $this->setOccurence($key);

    $id = $this->generateId();

    // Settings de base
    $settings = [
        'textarea_name' => $this->getControlName(),
        'drag_drop_upload' => true,
    ];

    // Attributs de l'item qu'on peut transmettre à l'éditeur
    $this->hasAttribute('rows') && $settings['textarea_rows'] = $this->getAttribute('rows');
    $this->hasAttribute('tabindex') && $settings['tabindex'] = $this->getAttribute('tabindex');
    $this->hasAttribute('class') && $settings['editor_class'] = $this->getAttribute('class'); // mode texte uniquement
    $this->hasAttribute('style') && $settings['editor_css'] = "<style scoped>#wp-$id-wrap{" . $this->getAttribute('style') . '}</style>';

    // Editeur en version simplifiée (cf. wp-admin/includes/class-wp-press-this.php:1403)
    if ($this->getTeeny()) {
        $settings['teeny'] = true;
        $settings['media_buttons'] = false;
        $settings['tinymce'] = [
            'resize'                => false,
            'wordpress_adv_hidden'  => false,
            'add_unload_trigger'    => false,
            'statusbar'             => false,
            'wp_autoresize_on'      => true,
            'plugins'               => 'lists,media,paste,tabfocus,fullscreen,wordpress,wpautoresize,wpeditimage,wpgallery,wplink,wptextpattern,wpview',
            'toolbar1'              => 'bold,italic,bullist,numlist,blockquote,link,unlink,undo,redo',
        ];
        $settings['quicktags'] = false;
    }

    wp_editor($data, $id, $settings);
}
$this->isRepeatable() && $theme->display($this, '_add');