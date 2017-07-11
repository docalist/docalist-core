<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Type;

use Docalist\Forms\Textarea;
use Docalist\Forms\WPEditor;
use InvalidArgumentException;

/**
 * Un bloc de texte multiligne contenant ou non du code html.
 */
class LargeText extends Text
{
    public function getAvailableEditors()
    {
        return [
            'textarea'       => __('Zone de texte sur plusieurs lignes', 'docalist-core'),
            'wpeditor'       => __('Editeur WordPress', 'docalist-core'),
            'wpeditor-teeny' => __('Editeur WordPress simplifié', 'docalist-core'),
        ];
    }

    public function getEditorForm($options = null)
    {
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());

        switch ($editor) {
            case 'textarea':
                $editor = new Textarea();
                $editor->addClass('autosize');
                break;

            case 'wpeditor':
                $editor = new WPEditor();
                break;

            case 'wpeditor-teeny':
                $editor = new WPEditor();
                $editor->setTeeny();
                break;

            default:
                throw new InvalidArgumentException("Invalid LargeText editor '$editor'");
        }

        return $editor
            ->setName($this->schema->name())
            ->setLabel($this->getOption('label', $options))
            ->setDescription($this->getOption('description', $options))
            ->setAttribute('rows', '1');
    }

    public function getFormattedValue($options = null)
    {
        $value = $this->phpValue;
        if (trim($value) === '') {
            return $value;
        }
        true && $value = wpautop($value);
        true && $value = wp_make_content_images_responsive($value);

        return $value;

        // Filtres wordpress (4.4) par défaut pour "the_content" (dans default-filters.php) :
        // Nom                                  Priorité    Rôle
        // capital_P_dangit()                   11          Change 'wordpress' en 'wordPress'...
        // wptexturize()                        10          Transforme les guillemets, apostrophes, etc.
        // convert_smilies()                    10          Convertit les smileys
        // wpautop()                            10          Remplace \n\n par des tags <p>..</p>
        // shortcode_unautop()                  10          Supprime les <p></p> en trop autour des shortcodes
        // prepend_attachment()                 10          Attachments uniquement : ajoute un lien
        // wp_make_content_images_responsive()  10          Ajoute des attr 'srcsets' et 'sizes' aux images
        // do_shortcode()                       11          Exécute les shortcodes

        // Voir s'il faut ou non ajouter des options à LargeText pour activer ou non ces filtres.
    }
}
