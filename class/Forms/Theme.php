<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Forms
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Forms;

use InvalidArgumentException;

/**
 * Un thème de formulaire.
 */
class Theme
{
    /**
     * Liste des thèmes connus / chargés.
     *
     * Initiallement, la liste contient les noms de classe des thèmes prédéfinis.
     * Lorsque get() est appellée, le thème est instancié et stocké dans la liste.
     * Si get() est appellée avec un nom de thème qu'on ne connaît pas, un filtre est
     * déclenché et on stocke le thème retourné.
     *
     * @var array
     */
    private static $themes = [
        'base' => 'Docalist\Forms\Theme\Base',
        'wordpress' => 'Docalist\Forms\Theme\WordPress',
    ];

    /**
     * Répertoire contenant les vues utilisées par ce thème.
     *
     * (path absolu avec séparateur final)
     *
     * @var string
     */
    protected $directory;

    /**
     * Thème parent de ce thème.
     *
     * @var Theme
     */
    protected $parent;

    /**
     * Dialecte html (xhtml, html4, html5) généré par ce thème.
     *
     * @var int
     */
    protected $dialect = 'html5';

    /**
     * Indique s'il faut indenter ou non le code html généré.
     *
     * Quand l'indentation est désactivée (valeur par défaut), la propriété
     * contient 'false'.
     *
     * Quand elle est activée, elle contient un entier qui indique le niveau
     * d'indentation en cours.
     *
     * @var false|int
     */
    protected $indent;

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
     * Cré un thème.
     *
     * @param string $directory Répertoire contenant les vues utilisées par ce thème.
     * @param Theme $parent Thème parent : si une vue n'existe pas dans le répertoire
     * du thème, elle sera recherchée dans le thème parent.
     * @param string $dialect Dialecte html (xhtml, html4, html5) généré par ce thème.
     *
     * @throws InvalidArgumentException Si le répertoire indiqué n'existe pas.
     */
    public function __construct($directory, Theme $parent = null, $dialect = null, $indent = false)
    {
        $path = realpath($directory);
        if ($path === false) {
            throw new InvalidArgumentException("Directory not found: $directory");
        }
        $this->directory = $path . DIRECTORY_SEPARATOR;
        $this->parent = $parent;
        !is_null($dialect) && $this->setDialect($dialect);
        $this->setIndent($indent);
    }

    /**
     * Retourne un thème.
     *
     * @param string|Theme $name Nom du thème ('base', 'wordpress'...)
     * Si $name est vide, le thème par défaut est retourné.
     * Si $name est déjà un objet thème, il est retourné tel quel.
     *
     * @throws InvalidArgumentException Si le thème demandé n'existe pas.
     *
     * @return Theme
     */
    final public static function get($name = null)
    {
        // Si on nous a passé un thème, on le retourne tel quel
        if ($name instanceof self) {
            return $name;
        }

        // Thème par défaut
        if (empty($name) || $name === 'default') {
            $name = apply_filters('docalist_forms_get_default_theme', 'base');
        }

        // Thème qu'on ne connaît pas encore
        if (!isset(self::$themes[$name])) {
            $theme = apply_filters("docalist_forms_get_{$name}_theme", null);

            if (is_null($theme)) {
                throw new InvalidArgumentException("Form theme '$name' not found.");
            }

            if (! $theme instanceof self) {
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
     *
     * @return string
     */
    final public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Retourne le thème parent du thème ou null si le thème n'a pas de thème parent.
     *
     * @return Theme|null
     */
    final public function getParent()
    {
        return $this->parent;
    }

    /**
     * Définit le dialecte html généré par le thème.
     *
     * Le dialecte utilisé influe sur la façon dont sont générés les tags et les attributs.
     * Par exemple, avec le dialecte xhtml on aura :
     * - <input type="checkbox" selected="selected" />
     * Alors que le dialecte html5 générera :
     * - <input type=checkbox selected>
     *
     * @param string $dialect 'xhtml', 'html4' ou 'html5'.
     *
     * @return self
     *
     * @throws InvalidArgumentException Si le dialexte indiqué n'est pas valide.
     */
    final public function setDialect($dialect)
    {
        if ($dialect !== 'xhtml' && $dialect !== 'html5' && $dialect !== 'html4') {
            throw new InvalidArgumentException("Invalid dialect '$dialect', expected xhtml, html4 or html5.");
        }
        $this->dialect = $dialect;

        return $this;
    }

    /**
     * Retourne le dialecte html généré par le thème.
     *
     * @return string $dialect 'xhtml', 'html4' ou 'html5'.
     */
    final public function getDialect()
    {
        return $this->dialect;
    }

    /**
     * Active ou désactive l'indentation du code html généré.
     *
     * @param bool $indent
     *
     * @return self
     */
    final public function setIndent($indent = true)
    {
        $this->indent = $indent ? 0 : false;

        return $this;
    }

    /**
     * Indique si l'indentation du code html généré est active ou non.
     *
     * @return bool
     */
    final public function getIndent()
    {
        return $this->indent === false ? false : true;
        // Comme $indent peut contenir 0, on ne peut pas utiliser (bool)$indent
    }

    /**
     * Affiche un item de formulaire en utilisant la vue indiquée.
     *
     * @param Item $item L'item à afficher.
     * @param string|null $view La vue à utiliser. Si $view est vide, la méthode getType() de
     * l'item est appellée et le résultat est utilisé comme nom de vue.
     *
     * @return self;
     *
     * @throws InvalidArgumentException Si la vue demandée n'existe ni dans le thème, ni dans
     * aucun de ses parents.
     */
    public function display(Item $item, $view = null, array $args = [])
    {
        static $level = 0;

        // Valide la vue demandée
        if (empty($view)) {
            $view = $item->getType();
        } elseif (! preg_match('~^[a-z_][a-z0-9-]+$~i', $view)) {
            throw new InvalidArgumentException("Invalid view name $view");
        }

        // Initialise le thème en cours
        ! isset($args['theme']) && $args['theme'] = $this;
        $theme = $args['theme']; /** @var Theme $theme */

        // Teste si ce thème contient la vue indiquée
        $path = $this->directory . $view . '.php';
        if (! file_exists($path)) {
            if (isset($this->parent)) {
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
        $view = function () use ($args, $theme) {
            require $args['path'];
        };

        // Binde la closure pour que $this soit dispo dans la vue
        $view = $view->bindTo($item, $item);

        // Exécute le template
        $path = basename(dirname($path)) . '/' . basename($path);
        ++$level;
        //$this->comment('enter ' . $path);
        $view();
        //$this->comment('leave ' . $path);
        --$level;

        // L'appel de plus haut niveau génère un enqueue des assets du thème
        ($level === 0) && $this->enqueueStyle($theme->styles)->enqueueScript($theme->scripts);

        // Ok
        return $this;
    }

    /**
     * Génère le code html d'un item de formulaire en utilisant la vue indiquée et retourne le
     * résultat.
     *
     * @param Item $item L'item à afficher.
     * @param string|null $view La vue à utiliser. Si $view est vide, la méthode getType() de
     * l'item est appellée et le résultat est utilisé comme nom de vue.
     *
     * @return string
     *
     * @throws InvalidArgumentException Si la vue demandée n'existe ni dans le thème, ni dans
     * aucun de ses parents.
     */
    final public function render(Item $item, $view = null)
    {
        ob_start();
        $this->display($item, $view);

        return ob_get_clean();
    }

    /**
     * Retourne une chaine contenant l'indentation en cours.
     *
     * @return string Une suite d'espaces si l'option 'indent' est activée, une
     * chaine vide sinon.
     */
    private function indent()
    {
        return $this->indent === false ? '' : str_repeat('    ', $this->indent);
    }

    /**
     * Retourne CRL/LF si l'indentation est activée, une chaine vide sinon.
     *
     * @return string
     */
    private function newline()
    {
        return $this->indent === false ? '' : "\n"; // ↩
    }

    /**
     * Encode les caractères spéciaux dans un bloc de texte.
     *
     * @param string $text
     *
     * @return self
     */
    protected function escapeText($text)
    {
        // Remarques :
        // - Dans du texte (contenu d'un tag), on a uniquement besoin d'escaper '<', '&' et '>'.
        // - Les guillemets doubles et simples peuvent être laissés tels quels (ENT_NOQUOTES)
        // - Comme on n'encode pas l'apostrophe, on n'a pas besoin de gérer les flags
        //   ENT_HTML401, ENT_HTML5, etc. (la seule différence, c'est que l'apostrophe peut
        //   être encodée '&apos;' en xml et en html5 mais doit être encodée '&#039;' en html4)
        // - Au cas où la chaine d'entrée contienne des séquences unicode incorrectes, on
        //   utilise ENT_SUBSTITUTE pour qu'elles soient remplacées par par "?" (U+FFFD)
        // - ENT_DISALLOWED est une sécu supplémentaire (séquence valide mais qui ne serait pas autorisée en html ?)
        return htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED, 'UTF-8');

        // remarque : Zend utilise ENT_QUOTES | ENT_SUBSTITUTE;
        // https://github.com/zendframework/zend-escaper/blob/master/src/Escaper.php#L144
    }

    /**
     * Encode les caractères spéciaux dans la valeur d'un attribut.
     *
     * @param string $value
     *
     * @return self
     */
    protected function escapeAttr($value)
    {
        // Remarques :
        // - Pour un attribut, on fait tout ce qu'on fait dans escapeText(), mais en plus on encode '"'
        // - Comme on contrôle la génération des attributs et qu'on utilise toujours des guillemets
        //   pour les délimiter (i.e. jamais des simples), on n'a pas besoin d'encoder l'apostrophe.
        // - On utilise donc ENT_COMPAT (qui signifie ENT_HTML_QUOTE_DOUBLE) pour encoder les guillemets
        //   et conserver les apostrophes.
        return htmlspecialchars($value, ENT_COMPAT | ENT_SUBSTITUTE | ENT_DISALLOWED, 'UTF-8');
    }

    /**
     * Encode les caractères spéciaux dans un commentaire.
     *
     * Adapté de la spécification html :
     * - Comments must start with the four character sequence "<!--".
     * - Following this sequence, the comment may have text, with the additional restriction that the text :
     *   1. must not start with a ">"
     *   2. must not start with a "->"
     *   3. must not contain "--"
     *   4. must not end with "-".
     * - Comment must be ended by the three character sequence "-->".
     *
     * Pour garantir 1, 2, et 4, l'encodeur ajoute un espace avant et après le texte du commentaire : ça
     * améliore la lisibilité des commentaires (<!-- x --> plutôt que <!--x-->) et c'est plus simple que
     * de remplacer ou de supprimer les séquences interdites.
     *
     * Pour garantir 3, on remplace '--' par '- -' (i.e. on insère un espace quand on trouve deux tirets
     * consécutifs).
     *
     * @param string $comment
     */
    protected function escapeComment($comment)
    {
        if (empty($comment)) {
            return ' ';
        }
        $count = 0;
        do {
            $comment = str_replace('--', '- -', $comment, $count);
        } while ($count);

        return ' ' . $comment . ' ';
    }

    /**
     * Génère un commentaire.
     *
     * Le contenu du commentaire est encodé de façon à ne contenir aucune des séquences interdites :
     * - un espace est ajout avant et après le contenu passé en paramètre.
     * - un espace est inséré quand une séquence de deux tirets consécutifs est détectée.
     *
     * @param string $html
     *
     * @return self
     */
    public function comment($comment)
    {
        echo $this->indent(), '<!--', $this->escapeComment($comment), '-->', $this->newline();

        return $this;
    }

    /**
     * Génère un bloc de texte en encodant les caractères spéciaux.
     *
     * @param string $text
     *
     * @return self
     */
    public function text($text)
    {
        echo $this->indent(), $this->escapeText($text), $this->newline();

        return $this;
    }

    /**
     * Génère un bloc html brut (aucun encodage).
     *
     * @param string $html
     *
     * @return self
     */
    public function html($html)
    {
        echo $this->indent(), $html, $this->newline();

        return $this;
    }

    /**
     * Génère un ou plusieurs attributs et retourne le résultat.
     *
     * attr(array) ou attr(name, value)
     *
     * @param string|array $attributes
     * @param string $value
     *
     * @return string;
     *
     * @throws InvalidArgumentException
     */
    protected function attr($attributes, $value = false)
    {
        is_string($attributes) && $attributes = [$attributes => $value];

        $result = '';
        foreach ($attributes as $name => $value) {
            // Ignore l'attribut si la valeur est vide et que l'attribut est optionnel
            if ($value === false || is_null($value) || ($value === '' && $this->isEmptyAttribute($name))) {
                continue;
            }

            // Gère les attributs booléens : on génère l'attribut s'il est activé, rien sinon
            if ($this->isBooleanAttribute($name)) {
                if ($value === true || $value === $name) {
                    $format = ($this->dialect === 'html5') ? ' %s' : ' %s="%s"';
                    $result .= sprintf($format, $name, $name);
                }
                continue;
            }

            // Si ce n'est pas un booléen, la valeur doit être un scalaire
            if (! is_scalar($value)) {
                $type = gettype($value);
                throw new InvalidArgumentException("Invalid value for attribute '$name', expected scalar, got $type.");
            }

            // Garantit qu'on a une chaine. Arrivé içi, ça peut encore être un entier : rows, cols...
            // Et dans ce cas, ctype_alnum retournera true pour 50 ('2') mais false pour 10 (LF)
            $value = (string) $value;

            // En html5, les guillemets sont optionnels si la valeur ne contient pas certains caractères
            if ($this->dialect === 'html5' && !$this->attributeNeedQuotes($value)) {
                $result .= sprintf(' %s=%s', $name, $value);
                continue;
            }

            // Attribut standard
            $result .= sprintf(' %s="%s"', $name, $this->escapeAttr($value));
        }

        return $result;
    }

    /**
     * Génère un tag.
     *
     * @param string $tag
     * @param array $attributes
     * @param string $content
     *
     * @return self
     *
     * @throws InvalidArgumentException
     */
    public function tag($tag, array $attributes = [], $content = null)
    {
        if ($this->isEmptyTag($tag)) {
            if (!empty($content)) {
                throw new InvalidArgumentException("Tag '$tag' can not have content.");
            }

            echo $this->indent(), '<', $tag, $this->attr($attributes);
            echo ($this->dialect === 'html5') ? '>' : '/>';
            echo $this->newline();

            return $this;
        }

        return $this->start($tag, $attributes)->html($content)->end($tag);
    }

    /**
     * Génère un tag ouvrant avec ses attributs.
     *
     * @param string $tag
     * @param array $attributes
     *
     * @return self;
     */
    public function start($tag, array $attributes = [])
    {
        if ($this->isEmptyTag($tag)) {
            throw new InvalidArgumentException("Tag '$tag' is an empty tag, start() cannot be used.");
        }

        echo $this->indent(), '<', $tag, $this->attr($attributes), '>', $this->newline();
        $this->indent !== false && ++$this->indent;

        return $this;
    }

    /**
     * Génère un tag fermant (ouvert via start).
     *
     * @param string $tag
     *
     * @return self
     */
    public function end($tag)
    {
        if ($this->isEmptyTag($tag)) {
            throw new InvalidArgumentException("Tag '$tag' is an empty tag, end() cannot be used.");
        }

        $this->indent !== false && --$this->indent;
        if ($this->dialect === 'html5' && $this->isOptionalTag($tag)) {
            return $this;
        }

        echo $this->indent(), '</', $tag, '>', $this->newline();

        return $this;
    }

    /**
     * Ajoute une ou plusieurs feuilles de styles css.
     *
     * Alias de la fonction wordpress wp_enqueue_style().
     *
     * @param string|string[] $handles Un ou plusieurs handles de feuille de
     * styles css préalablement déclarés via wp_register_style().
     *
     * @return self
     */
    public function enqueueStyle($handles)
    {
        wp_styles()->enqueue($handles);

        return $this;
    }

    /**
     * Ajoute un ou plusieurs scripts javascript.
     *
     * Alias de la fonction wordpress wp_enqueue_script().
     *
     * @param string|string[] $handles Un ou plusieurs handles de scripts
     * javascript préalablement déclarés via wp_register_script().
     *
     * @return self
     */
    public function enqueueScript($handles)
    {
        wp_scripts()->enqueue($handles);

        return $this;
    }

    /**
     * Indique si l'attribut indiqué est un attribut booléen (checked, selected, etc.).
     *
     * cf. http://www.w3.org/TR/html5/infrastructure.html#boolean-attribute
     *
     * @param string $name Le nom de l'attribut à tester.
     *
     * @return bool
     */
    final private function isBooleanAttribute($attribute)
    {
        // Sources :
        // - https://github.com/kangax/html-minifier/issues/63
        // - https://github.com/kangax/html-minifier/blob/gh-pages/src/htmlminifier.js

        $bool = '|allowfullscreen|async|autofocus|autoplay|checked|compact|controls|declare|default|defaultchecked|
                 |defaultmuted|defaultselected|defer|disabled|enabled|formnovalidate|hidden|indeterminate|inert|ismap|
                 |itemscope|loop|multiple|muted|nohref|noresize|noshade|novalidate|nowrap|open|pauseonexit|readonly|
                 |required|reversed|scoped|seamless|selected|sortable|spellcheck|truespeed|typemustmatch|visible|';

        return false !== stripos($bool, "|$attribute|");

        // à gérer ? draggable (true, false, auto)
    }

    /**
     * Teste si des guillemets sont nécessaire autour de la valeur d'attribut passé en paramètre.
     *
     * @param string $attr
     *
     * @return bool
     */
    final private function attributeNeedQuotes($attr)
    {
        // A valid unquoted attribute value in HTML is any string of text
        // - that is not the empty string and
        // - that doesn't contain spaces, tabs, line feeds, form feeds, carriage returns, ", ', `, =, <, or >.
        // Source : http://mathiasbynens.be/notes/unquoted-attribute-values
        if ($attr === '') {
            return true;
        }

        if (preg_match('~[\x20\t\n\f\r"\'`=<>]~', $attr)) {
            return true;
        }

        // Make sure trailing slash is not interpreted as HTML self-closing tag
        // Source : http://kangax.github.io/html-minifier/
        if (substr($attr, -1) === '/') {
            return true;
        }

        // Ok
        return false;
    }

    /**
     * Teste si l'attribut dont le nom est passé en paramètre peut être supprimé
     * lorsqu'il est vide.
     *
     * @param string $attr Le nom de l'attribut à tester.
     *
     * @return bool
     */
    final private function isEmptyAttribute($attr)
    {
        // Source : http://kangax.github.io/html-minifier/
        $attrs = '|class|id|style|title|lang|dir|onfocus|onblur|onchange|onclick|ondblclick|onmousedown|
                  |onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|onkeydown|onkeyup|';

        // En html5, <form action=""> est interdit, il faut enlever action
        // Source : http://stackoverflow.com/a/1132015/1924128
        $this->dialect === 'html5' && $attrs .= 'action|';


        return false !== stripos($attrs, "|$attr|");
    }

    /**
     * Indique si le tag passé en paramètre peut avoir un contenu (par exemple <p>) ou
     * non (par exemple <br />).
     *
     * @param string $tag
     *
     * @return bool
     */
    final private function isEmptyTag($tag)
    {
        // Sources :
        // - https://developer.mozilla.org/en-US/docs/Glossary/empty_element
        // - http://xahlee.info/js/html5_non-closing_tag.html
        $tags = '|area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr|';

        // Remarques :
        // - embed ne figure pas dans la liste moz (html4) mais il figure dans la liste xahlee
        // - colgroup non ajouté car il n'est pas *toujours* vide
        return false !== stripos($tags, "|$tag|");
    }

//     /**
//      * Indique si le tag passé en paramètre est un élément inline.
//      *
//      * @param string $tag
//      *
//      * @return bool
//      */
//     final public static function isInlineElement($tag)
//     {
//         // Source :
//         // - https://developer.mozilla.org/en-US/docs/Web/HTML/Inline_elemente
//         // - mais plusieurs ont été enlevés de la liste pour que ça indente comme on veut
//         $tags = '|b|big|i|small|tt|abbr|acronym|cite|code|dfn|em|kbd|strong|samp|time|var|a|bdo|br|img|
//                  |map|object|q|script|span|sub|sup|button|input|label|select|textarea|';
//
//         // Remarques :
//         // - embed ne figure pas dans la liste moz (html4) mais il figure dans la liste xahlee
//         // - colgroup non ajouté car il n'est pas *toujours* vide
//         return false !== stripos($tags, "|$tag|");
//     }

    /**
     * Indique si le tag de fin passé en paramètre peut être omis en html5.
     *
     * @param string $tag
     *
     * @return bool
     */
    final private function isOptionalTag($tag)
    {
        // Source :
        // - https://github.com/kangax/html-minifier/blob/gh-pages/src/htmlminifier.js
        $tags = '|html|body|tbody|head|thead|tfoot|tr|td|th|dt|dd|option|colgroup|source|track|';

        // Remarques :
        return false !== stripos($tags, "|$tag|");
    }
}
