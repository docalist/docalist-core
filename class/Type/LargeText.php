<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Type;

use Docalist\Forms\CodeEditor;
use Docalist\Forms\Container;
use Docalist\Forms\Element;
use Docalist\Forms\Textarea;
use Docalist\Forms\WPEditor;
use WP_Embed;

/**
 * Un bloc de texte multiligne contenant ou non du code html.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class LargeText extends Text
{
    /**
     * {@inheritDoc}
     */
    public function getAvailableEditors(): array
    {
        return [
            'textarea'       => __('Zone de texte sur plusieurs lignes', 'docalist-core'),
            'wpeditor'       => __('Editeur WordPress', 'docalist-core'),
            'wpeditor-teeny' => __('Editeur WordPress simplifié', 'docalist-core'),
            'csseditor'      => __('Editeur de code CSS', 'docalist-core'),
            'htmleditor'     => __('Editeur de code HTML', 'docalist-core'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getEditorForm($options = null): Element
    {
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());
        $css = '';
        switch ($editor) {
            case 'textarea':
                $form = new Textarea();
                $css = 'autosize large-text';
                break;

            case 'wpeditor':
                $form = new WPEditor();
                break;

            case 'wpeditor-teeny':
                $form = new WPEditor();
                $form->setTeeny();
                break;

            case 'csseditor':
                $form = new CodeEditor();
                $form->setOptions(['type' => 'text/css']);
                $css = 'autosize large-text'; // comme textarea si l'utilisateur a désactivé codemirror
                break;

            case 'htmleditor':
                $form = new CodeEditor();
                $form->setOptions([
                    'type'       => 'text/html',
                    'codemirror' => [
                        'matchTags' => false,
                    ],
                ]);
                $css = 'autosize large-text'; // comme textarea si l'utilisateur a désactivé codemirror
                break;

            default:
                return parent::getEditorForm($options);
        }

        $form->setAttribute('rows', '1');
        $form->addClass($css);

        return $this->configureEditorForm($form, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getFormatSettingsForm(): Container
    {
        $form = parent::getFormatSettingsForm();
        $form->checklist('filters')
            ->setLabel(__('Filtres WordPress', 'docalist-core'))
            ->setDescription(__(
                'Par défaut, le contenu du champ est affiché tel quel. Vous pouvez activer les options
                proposées si vous voulez que votre champ se comporte comme un article WordPress standard.',
                'docalist-core'
            ))
            ->setOptions([
                'autop'             => 'Paragraphes auto (crée des paragraphes html quand on a deux retours à la ligne)',
                'texturize'         => 'Texturize (version typographique des apostrophes, guillemets, tirets, ellipses...)',
                'smilies'           => 'Smileys (convertit les émoticônes comme :-) en smileys)',
                'autoembed'         => 'Auto-embed (intègre les médias liés dans le contenu)',
                'shortcodes'        => 'Shortcodes (exécute les shortcodes présents dans le contenu)',
                'responsive-images' => 'Formats multiples pour les images (pour les rendre responsive)',
            ]);

        return $form;
    }

    /**
     * Retourne l'objet WP_Embed de WordPress (pour éviter de coder des "global" partout).
     */
    private function wpEmbed(): WP_Embed
    {
        return $GLOBALS['wp_embed'];
    }

    /**
     * {@inheritDoc}
     */
    public function getFormattedValue($options = null): string
    {
        $value = $this->getPhpValue();
        if ('' === trim($value)) {
            return $value;
        }

        $filters = $this->getOption('filters', $options, ['autop', 'responsive-images']);

        if (in_array('autoembed', $filters)) {
            $value = $this->wpEmbed()->run_shortcode($value);
            $value = $this->wpEmbed()->autoembed($value);
        }

        if (in_array('texturize', $filters)) {
            $value = wptexturize($value);
        }

        if (in_array('smilies', $filters)) {
            $value = convert_smilies($value);
        }

        if (in_array('autop', $filters)) {
            $value = wpautop($value);
            $value = shortcode_unautop($value);
        }

        if (in_array('responsive-images', $filters)) {
            $value = wp_filter_content_tags($value);
        }

        if (in_array('shortcodes', $filters)) {
            $value = do_shortcode($value);
        }

        return $value;

        // Filtres wordpress (5.2.5) par défaut pour "the_content" :
        //
        // Dans default-filters.php :
        // capital_P_dangit()                   11          Change 'wordpress' en 'wordPress'...
        // do_blocks()                           9          Fait le rendu des blocs gutenberg
        // wptexturize()                        10          Transforme les guillemets, apostrophes, etc.
        // convert_smilies()                    20          Convertit les smileys
        // wpautop()                            10          Remplace \n\n par des tags <p>..</p>
        // shortcode_unautop()                  10          Supprime les <p></p> en trop autour des shortcodes
        // prepend_attachment()                 10          Attachments uniquement : ajoute un lien
        // wp_make_content_images_responsive()  10          Ajoute des attr 'srcsets' et 'sizes' aux images
        // do_shortcode()                       11          Exécute les shortcodes
        //
        // Dans class-wp-embed.php :
        // $wp_embed->run_shortcode()            8          Hack pour exécuter le shortcode [embed] avant wp_autop
        // $wp_embed->autoembed()                8          Embed automatique pour les urls reconnues
    }
}
