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

use Docalist\Forms\Theme\BaseTheme;
use Docalist\Forms\Theme\WordPressTheme;
use Docalist\Html;
use InvalidArgumentException;

/**
 * Un thème de formulaire.
 *
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
class Theme extends Html
{
    /**
     * Liste des thèmes connus / chargés.
     *
     * Initiallement, la liste contient les noms de classe des thèmes prédéfinis. Lorsque get() est appellée,
     * le thème est instancié et stocké dans la liste. Si get() est appellée avec un nom de thème qu'on ne
     * connaît pas, un filtre est déclenché et on stocke le thème retourné.
     *
     * @var array
     */
    private static $themes = [
        'base' => BaseTheme::class,
        'wordpress' => WordPressTheme::class,
    ];

    /**
     * Répertoire contenant les vues utilisées par ce thème.
     *
     * (path absolu avec séparateur final)
     */
    protected string $directory;

    /**
     * Thème parent de ce thème.
     */
    protected ?Theme $parent;

    /**
     * Handles des styles CSS nécessaires pour ce thème.
     *
     * @var string[]
     */
    protected $styles = [];

    /**
     * Handles des scripts JS nécessaires pour ce thème.
     *
     * @var string[]
     */
    protected $scripts = ['docalist-forms'];

    /**
     * Crée un thème.
     *
     * @param string $directory répertoire contenant les vues utilisées par ce thème
     * @param ?Theme $parent    thème parent : si une vue n'existe pas dans le répertoire du thème,
     *                          elle sera recherchée dans le thème parent
     * @param string $dialect   dialecte html (xhtml, html4, html5) généré par ce thème
     *
     * @throws InvalidArgumentException si le répertoire indiqué n'existe pas
     */
    public function __construct(string $directory, Theme $parent = null, string $dialect = 'html5')
    {
        parent::__construct($dialect);
        if (false === $path = realpath($directory)) {
            throw new InvalidArgumentException("Directory not found: $directory");
        }
        $this->directory = $path.DIRECTORY_SEPARATOR;
        $this->parent = $parent;
    }

    /**
     * Retourne un thème.
     *
     * @param string|Theme $name Nom du thème ('base', 'wordpress'...)
     *
     * - Si $name est vide, le thème par défaut est retourné.
     * - Si $name est déjà un objet thème, il est retourné tel quel.
     *
     * @throws InvalidArgumentException si le thème demandé n'existe pas
     */
    final public static function get($name = null): Theme
    {
        // Si on nous a passé un thème, on le retourne tel quel
        if ($name instanceof Theme) {
            return $name;
        }

        // Thème par défaut
        if (null === $name || '' === $name || 'default' === $name) {
            $name = apply_filters('docalist_forms_get_default_theme', 'wordpress');
        }

        // Thème qu'on ne connaît pas encore
        if (!isset(self::$themes[$name])) {
            $theme = apply_filters("docalist_forms_get_{$name}_theme", null);

            if (is_null($theme)) {
                throw new InvalidArgumentException("Form theme '$name' not found.");
            }

            if (!$theme instanceof Theme) {
                throw new InvalidArgumentException("Invalid theme returned by 'docalist_forms_get_{$name}_theme'.");
            }

            return self::$themes[$name] = $theme;
        }

        // Thème qu'on connaît mais qui n'a pas encore été instancié
        if (is_string(self::$themes[$name])) {
            $class = self::$themes[$name];
            self::$themes[$name] = new $class();
        }

        // Thème déjà instancié
        return self::$themes[$name];
    }

    /**
     * Retourne le répertoire contenant les vues utilisées par ce thème.
     */
    final public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Retourne le thème parent du thème ou null si le thème n'a pas de thème parent.
     */
    final public function getParent(): ?Theme
    {
        return $this->parent;
    }

    /**
     * Affiche un item de formulaire en utilisant la vue indiquée.
     *
     * @param Item        $item L'item à afficher
     * @param string|null $view La vue à utiliser. Si $view est vide, la méthode getType() de l'item
     *                          est appellée et le résultat est utilisé comme nom de vue.
     *
     * @throws InvalidArgumentException si la vue demandée n'existe ni dans le thème, ni dans aucun de ses parents
     */
    public function display(Item $item, string $view = null, array $args = []): static
    {
        static $level = 0;

        // Valide la vue demandée
        if (null === $view || '' === $view) {
            $view = $item->getType();
        } elseif (!preg_match('~^[a-z_][a-z0-9-]+$~i', $view)) {
            throw new InvalidArgumentException("Invalid view name $view");
        }

        // Initialise le thème en cours
        if (!isset($args['theme'])) {
            $args['theme'] = $this;
        }
        $theme = $args['theme']; /* @var Theme $theme */

        // Teste si ce thème contient la vue indiquée
        $path = $this->directory.$view.'.php';
        if (!file_exists($path)) {
            if ($this->parent instanceof Theme) {
                // On demande simplement au parent d'afficher la vue
                // Mais on veut qu'il utilise les mêmes options que nous (dialect, indent)
                // Donc on fait une sauvegarde des options du parent, on les modifie temporairement,
                // puis on affiche la vue et on rétablit les options d'origine.
                $dialect = $this->parent->dialect;
                $indent = $this->parent->indent;
                $this->parent->dialect = $this->dialect;
                $this->parent->indent = $this->indent;

                $this->parent->display($item, $view, $args);

                $this->parent->dialect = $dialect;
                $this->parent->indent = $indent;

                return $this;
            }
            throw new InvalidArgumentException("Form view '$view' not found.");
        }

        // Crée la closure qui va exécuter le template (sandbox)
        $args['path'] = $path;
        // @phpstan-ignore-next-line : Anonymous function has an unused use $theme.
        $view = function () use ($args, $theme): void {
            // le use($theme) est requis pour que $theme soit accessible dans le template
            require $args['path'];
        };

        // Binde la closure pour que $this soit dispo dans la vue
        $view = $view->bindTo($item, $item);

        // Exécute le template
        ++$level;
        $view();
        --$level;

        // L'appel de plus haut niveau génère un enqueue des assets du thème
        if (0 === $level) {
            $this->enqueueStyle($theme->styles)->enqueueScript($theme->scripts);
        }

        // Ok
        return $this;
    }

    /**
     * Génère le code html d'un item de formulaire en utilisant la vue indiquée et retourne le résultat.
     *
     * @param Item        $item L'item à afficher
     * @param string|null $view La vue à utiliser. Si $view est vide, la méthode getType() de l'item
     *                          est appellée et le résultat est utilisé comme nom de vue.
     *
     * @throws InvalidArgumentException si la vue demandée n'existe ni dans le thème, ni dans aucun de ses parents
     */
    final public function render(Item $item, string $view = null): string
    {
        ob_start();
        $this->display($item, $view);

        return ob_get_clean();
    }

    /**
     * Ajoute une ou plusieurs feuilles de styles css.
     *
     * Alias de la fonction wordpress wp_enqueue_style().
     *
     * @param string|string[] $handles un ou plusieurs handles de feuille de styles css préalablement déclarés
     *                                 via wp_register_style()
     */
    public function enqueueStyle($handles): static
    {
        wp_styles()->enqueue($handles);

        return $this;
    }

    /**
     * Ajoute un ou plusieurs scripts javascript.
     *
     * Alias de la fonction wordpress wp_enqueue_script().
     *
     * @param string|string[] $handles un ou plusieurs handles de scripts javascript préalablement déclarés via
     *                                 wp_register_script()
     */
    public function enqueueScript($handles): static
    {
        wp_scripts()->enqueue($handles);

        return $this;
    }
}
