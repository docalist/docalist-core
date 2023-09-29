<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2023 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Forms;

/**
 * Un éditeur de code basé sur CodeMirror.
 *
 * Références :
 * {@link https://make.wordpress.org/core/2017/10/22/code-editing-improvements-in-wordpress-4-9/}.
 * {@link https://codemirror.net/doc/manual.html}.
 *
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
final class CodeEditor extends Textarea
{
    /**
     * Options par défaut de l'éditeur.
     *
     * Voir la description du paramètre $arg de la fonction wp_enqueue_code_editor().
     *
     * @var array
     */
    protected static $defaultOptions = [
        // Mode par défaut
        'type' => 'text/html',
        'codemirror' => [
            // On utilise le thème default (le seul disponible dans WordPress) et on ajoute le pseudo-thème autosize
            // qui a pour effet de générer une classe CSS ".cm-s-autosize" (définie dans wordpress-theme.css)
            'theme' => 'default autosize',

            // En général, on édite seulement des bouts de code et les numéros de ligne ne sont pas très utiles
            'lineNumbers' => false,

            // Désactive la mise en surbrillance de la ligne en cours car CodeMirror le fait même quand l'éditeur
            // n'a pas le focus. Quand on a plusieurs éditeurs sur la même page, c'est trompeur.
            'styleActiveLine' => false,
        ],
    ];

    /**
     * Options de l'éditeur.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Retourne les options de l'éditeur de code.
     */
    public function getOptions(): array
    {
        return array_replace_recursive(self::$defaultOptions, $this->options);
    }

    /**
     * Modifie les options de l'éditeur de code.
     *
     * @param array $options les options qui seront passées à la fonction wp_enqueue_code_editor() de WordPress
     */
    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }
}
