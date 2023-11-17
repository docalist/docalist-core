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

namespace Docalist\Forms;

use Docalist\Forms\Theme\BaseTheme;
use Docalist\Forms\Theme\WordPressTheme;
use Docalist\Forms\Theme\XhtmlTheme;

/**
 * Liste des thèmes de formulaire.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Themes
{
    /**
     * Liste des thèmes connus / chargés.
     *
     * Initiallement, la liste contient les noms de classe des thèmes prédéfinis. Lorsque get() est appellée,
     * le thème est instancié et stocké dans la liste. Si get() est appellée avec un nom de thème qu'on ne
     * connaît pas, un filtre est déclenché et on stocke le thème retourné.
     *
     * @var array<class-string|Theme>
     */
    private static array $themes = [
        'xhtml' => XhtmlTheme::class,
        'base' => BaseTheme::class,
        'wordpress' => WordPressTheme::class,
    ];

    /**
     * Retourne un thème.
     *
     * @param string|Theme $name Nom du thème ('base', 'wordpress'...)
     *
     * - Si $name est vide, le thème par défaut est retourné.
     * - Si $name est déjà un objet thème, il est retourné tel quel.
     *
     * @throws \InvalidArgumentException si le thème demandé n'existe pas
     */
    final public static function get(string|Theme $name = ''): Theme
    {
        // Si on nous a passé un thème, on le retourne tel quel
        if ($name instanceof Theme) {
            return $name;
        }

        // Thème par défaut
        if ($name === '' || 'default' === $name) {
            $name = apply_filters('docalist_forms_get_default_theme', 'wordpress');
        }

        // Thème qu'on ne connaît pas encore
        if (!isset(self::$themes[$name])) {
            $theme = apply_filters("docalist_forms_get_{$name}_theme", null);

            if (is_null($theme)) {
                throw new \InvalidArgumentException("Form theme '$name' not found.");
            }

            if (!$theme instanceof Theme) {
                throw new \InvalidArgumentException("Invalid theme returned by 'docalist_forms_get_{$name}_theme'.");
            }

            return self::$themes[$name] = $theme;
        }

        // Thème qu'on connaît mais qui n'a pas encore été instancié
        if (is_string(self::$themes[$name])) {
            $class = self::$themes[$name];
            /** @var Theme $theme */
            $theme = new $class();
            self::$themes[$name] = $theme;
        }

        // Thème déjà instancié
        return self::$themes[$name];
    }
}
