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

namespace Docalist;

/**
 * Service de génération de code Html.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Html
{
    /**
     * Dialecte html (xhtml, html4, html5) généré.
     *
     * @var string
     */
    protected $dialect;

    /**
     * Indique s'il faut indenter ou non le code html généré.
     *
     * Quand l'indentation est désactivée (valeur par défaut), la propriété contient 'false'.
     *
     * Quand elle est activée, elle contient un entier qui indique le niveau d'indentation en cours.
     *
     * @var false|int
     */
    protected $indent = false;

    /**
     * Initialise le service.
     *
     * @param string $dialect dialecte html (xhtml, html4, html5) généré par ce thème (html5 par défaut)
     */
    public function __construct(string $dialect = 'html5')
    {
        $this->setDialect($dialect);
    }

    /**
     * Définit le dialecte html généré par le thème.
     *
     * Le dialecte utilisé influe sur la façon dont sont générés les tags et les attributs.
     *
     * Par exemple, avec le dialecte xhtml on aura :
     * - <input type="checkbox" selected="selected" />
     *
     * Alors que le dialecte html5 générera :
     * - <input type=checkbox selected>
     *
     * @param string $dialect 'xhtml', 'html4' ou 'html5'
     */
    final public function setDialect(string $dialect): static
    {
        $this->dialect = $dialect;

        return $this;
    }

    /**
     * Retourne le dialecte html généré par le thème.
     *
     * @return string $dialect 'xhtml', 'html4' ou 'html5'
     */
    final public function getDialect(): string
    {
        return $this->dialect;
    }

    /**
     * Active ou désactive l'indentation du code html généré.
     */
    final public function setIndent(bool $indent): static
    {
        $this->indent = $indent ? 0 : false;

        return $this;
    }

    /**
     * Indique si l'indentation du code html généré est active ou non.
     */
    final public function getIndent(): bool
    {
        return !(false === $this->indent); // $indent peut être à 0, on ne peut pas utiliser (bool)$indent
    }

    /**
     * Retourne une chaine contenant l'indentation en cours.
     *
     * @return string une suite d'espaces si l'option 'indent' est activée, une chaine vide sinon
     */
    private function indent(): string
    {
        return $this->indent ? str_repeat('    ', $this->indent) : '';
    }

    /**
     * Retourne CR/LF si l'indentation est activée, une chaine vide sinon.
     */
    private function newline(): string
    {
        return false === $this->indent ? '' : "\n"; // ↩
    }

    /**
     * Encode les caractères spéciaux dans un bloc de texte.
     */
    protected function escapeText(string $text): string
    {
        // - Dans du texte (contenu d'un tag), on a uniquement besoin d'escaper '<', '&' et '>'.
        // - Les guillemets doubles et simples peuvent être laissés tels quels (ENT_NOQUOTES)
        // - Comme on n'encode pas l'apostrophe, on n'a pas besoin de gérer les flags
        //   ENT_HTML401, ENT_HTML5, etc. (la seule différence, c'est que l'apostrophe peut
        //   être encodée '&apos;' en xml et en html5 mais doit être encodée '&#039;' en html4)
        // - Au cas où la chaine d'entrée contienne des séquences unicode incorrectes, on
        //   utilise ENT_SUBSTITUTE pour qu'elles soient remplacées par par "?" (U+FFFD)
        // - ENT_DISALLOWED est une sécu supplémentaire (séquence valide mais qui ne serait pas autorisée en html ?)
        return htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED, 'UTF-8');

        // Zend utilise ENT_QUOTES | ENT_SUBSTITUTE;
        // https://github.com/zendframework/zend-escaper/blob/master/src/Escaper.php#L144
    }

    /**
     * Encode les caractères spéciaux dans la valeur d'un attribut.
     */
    protected function escapeAttr(string $value): string
    {
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
     */
    protected function escapeComment(string $comment): string
    {
        if (empty($comment)) {
            return ' ';
        }
        $count = 0;
        do {
            $comment = str_replace('--', '- -', $comment, $count);
        } while (0 !== $count);

        return ' '.$comment.' ';
    }

    /**
     * Génère un commentaire.
     *
     * Le contenu du commentaire est encodé de façon à ne contenir aucune des séquences interdites :
     * - un espace est ajout avant et après le contenu passé en paramètre.
     * - un espace est inséré quand une séquence de deux tirets consécutifs est détectée.
     */
    public function comment(string $comment): static
    {
        echo $this->indent(), '<!--', $this->escapeComment($comment), '-->', $this->newline();

        return $this;
    }

    /**
     * Génère un bloc de texte en encodant les caractères spéciaux.
     */
    public function text(string $text): static
    {
        echo $this->indent(), $this->escapeText($text), $this->newline();

        return $this;
    }

    /**
     * Génère un bloc html brut (aucun encodage).
     */
    public function html(string $html): static
    {
        echo $this->indent(), $html, $this->newline();

        return $this;
    }

    /**
     * Génère des attributs et retourne le résultat.
     *
     * @param array<string,string|int|bool|null> $attributes un tableau de la forme nom=>valeur contenant les attributs à générer
     *
     * @return string une chaine contenant les attributs générés
     */
    protected function attr(array $attributes): string
    {
        $result = '';
        foreach ($attributes as $name => $value) {
            // Ignore l'attribut si la valeur est vide et que l'attribut est optionnel
            if ($this->isEmptyAttribute($name, $value)) {
                continue;
            }

            // Gère les attributs booléens : on génère l'attribut s'il est activé, rien sinon
            if ($this->isBooleanAttribute($name)) {
                if (true === $value || $value === $name) {
                    $format = ('html5' === $this->dialect) ? ' %s' : ' %s="%s"';
                    $result .= sprintf($format, $name, $name);
                }
                continue;
            }

            // Garantit qu'on a une chaine. Arrivé içi, ça peut encore être un entier : rows, cols...
            // Et dans ce cas, ctype_alnum retournera true pour 50 ('2') mais false pour 10 (LF)
            $value = (string) $value;

            // En html5, les guillemets sont optionnels si la valeur ne contient pas certains caractères
            if ('html5' === $this->dialect && !$this->attributeNeedQuotes($value)) {
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
     * @param string $tag        nom du tag à générer
     * @param array<string,string|int|bool|null>  $attributes attributs du tag
     * @param string $content    contenu du tag
     */
    public function tag(string $tag, array $attributes = [], string $content = ''): static
    {
        if ($this->isEmptyTag($tag)) {
            echo $this->indent(), '<', $tag, $this->attr($attributes);
            echo ('html5' === $this->dialect) ? '>' : '/>';
            echo $this->newline();

            return $this;
        }

        return $this->start($tag, $attributes)->html($content)->end($tag);
    }

    /**
     * Génère un tag ouvrant avec ses attributs.
     *
     * @param string $tag        nom du tag à générer
     * @param array<string,string|int|bool|null>  $attributes attributs du tag
     */
    public function start(string $tag, array $attributes = []): static
    {
        echo $this->indent(), '<', $tag, $this->attr($attributes), '>', $this->newline();
        false !== $this->indent && ++$this->indent;

        return $this;
    }

    /**
     * Génère un tag fermant (ouvert via start).
     *
     * @param string $tag nom du tag à générer
     */
    public function end(string $tag): static
    {
        false !== $this->indent && --$this->indent;
        if ('html5' === $this->dialect && $this->isOptionalTag($tag)) {
            return $this;
        }

        echo $this->indent(), '</', $tag, '>', $this->newline();

        return $this;
    }

    /**
     * Indique si l'attribut indiqué est un attribut booléen (checked, selected, etc.).
     *
     * cf. http://www.w3.org/TR/html5/infrastructure.html#boolean-attribute
     *
     * @param string $attribute le nom de l'attribut à tester
     */
    private function isBooleanAttribute(string $attribute): bool
    {
        // Sources
        // - https://github.com/kangax/html-minifier/issues/63
        // - https://github.com/kangax/html-minifier/blob/gh-pages/src/htmlminifier.js

        $bool = '|allowfullscreen|async|autofocus|autoplay|checked|compact|controls|declare|default|defaultchecked|
                 |defaultmuted|defaultselected|defer|disabled|enabled|formnovalidate|hidden|indeterminate|inert|ismap|
                 |itemscope|loop|multiple|muted|nohref|noresize|noshade|novalidate|nowrap|open|pauseonexit|readonly|
                 |required|reversed|scoped|seamless|selected|sortable|spellcheck|truespeed|typemustmatch|visible|';

        return false !== stripos($bool, "|$attribute|");

        // non géré "draggable" qui peut être true, false ou auto
    }

    /**
     * Teste si des guillemets sont nécessaires autour de la valeur d'attribut passé en paramètre.
     *
     * @param string $value la valeur d'attribut à tester
     */
    private function attributeNeedQuotes(string $value): bool
    {
        // A valid unquoted attribute value in HTML is any string of text
        // - that is not the empty string and
        // - that doesn't contain spaces, tabs, line feeds, form feeds, carriage returns, ", ', `, =, <, or >.
        // Source : http://mathiasbynens.be/notes/unquoted-attribute-values
        if ('' === $value) {
            return true;
        }

        if (preg_match('~[\x20\t\n\f\r"\'`=<>]~', $value)) {
            return true;
        }

        // Make sure trailing slash is not interpreted as HTML self-closing tag
        // Source : http://kangax.github.io/html-minifier/
        return '/' === substr($value, -1);
    }

    /**
     * Teste si l'attribut passé en paramètre peut être supprimé lorsque sa valeur est vide.
     *
     * @param string           $name  le nom de l'attribut à tester
     * @param string|bool|int|null $value la valeur de l'attribut
     */
    private function isEmptyAttribute(string $name, $value): bool
    {
        if (false === $value || is_null($value)) {
            return true;
        }

        if ('' !== $value) {
            return false;
        }

        // On a une valeur non vide, on teste le nom de l'attribut pour savoir s'il est optionnel

        // Source http://kangax.github.io/html-minifier/
        $attrs = '|class|id|style|title|lang|dir|onfocus|onblur|onchange|onclick|ondblclick|onmousedown|
                  |onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|onkeydown|onkeyup|';

        // En html5, <form action=""> est interdit, il faut enlever action
        // Source : http://stackoverflow.com/a/1132015/1924128
        'html5' === $this->dialect && $attrs .= 'action|';

        return false !== stripos($attrs, "|$name|");
    }

    /**
     * Indique si le tag passé en paramètre peut avoir un contenu (exemple <p>) ou non (exemple <br />).
     *
     * @param string $tag le nom du tag à tester
     */
    private function isEmptyTag(string $tag): bool
    {
        // https://developer.mozilla.org/en-US/docs/Glossary/empty_element
        // http://xahlee.info/js/html5_non-closing_tag.html
        $tags = '|area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr|';

        // - embed ne figure pas dans la liste moz (html4) mais il figure dans la liste xahlee
        // - colgroup non ajouté car il n'est pas *toujours* vide
        return false !== stripos($tags, "|$tag|");
    }

    /**
     * Indique si le tag de fin peut être omis en html5 pour tag dont le nom est passé en paramètre.
     *
     * @param string $tag le nom du tag à tester
     */
    private function isOptionalTag(string $tag): bool
    {
        // https://github.com/kangax/html-minifier/blob/gh-pages/src/htmlminifier.js
        $tags = '|html|body|tbody|head|thead|tfoot|tr|td|th|dt|dd|option|colgroup|source|track|';

        return false !== stripos($tags, "|$tag|");
    }
}
