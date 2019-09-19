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

use Docalist\Forms\Traits\AttributesTrait;
use InvalidArgumentException;

/**
 * Un tag html.
 *
 * Caractéristiques :
 * - c'est un HtmlBlock
 * - a un nom de tag
 * - possède des attributs
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Tag extends HtmlBlock
{
    use AttributesTrait;

    /**
     * @var string Le nom du tag.
     */
    protected $tag;

    /**
     * Crée un nouveau tag.
     *
     * @param string    $tag        Optionnel, le tag de l'élément (div par défaut).
     * @param string    $content    Optionnel, le contenu de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     * @param Container $parent     Optionnel, le containeur parent de l'item.
     */
    public function __construct(string $tag = 'div', string $content = '', array $attributes = [], Container $parent = null)
    {
        parent::__construct($content, $parent);
        $this->setTag($tag);
        !empty($attributes) && $this->addAttributes($attributes);
    }

    /**
     * Modifie le nom du tag.
     *
     * @param string $tag Le nom tag.
     *
     * Vous pouvez passer en paramétre un tag simple ('p', 'span'...) ou un sélecteur style CSS de la
     * forme "tag[name]#id.class" qui permet de définir, en plus du nom du tag, les attributs name, id et
     * class de l'élément.
     *
     * Par exemple, un sélecteur de la forme <code>'input[age]#age.date.required'</code> créera un élément du style :
     *
     *     <code><input name="age" id="age" class="date required" /></code>
     *
     * Tous les éléments du sélecteur sont optionnels (sauf le nom de tag), mais ils doivent apparaître dans
     * l'ordre indiqué ('p#id.class' fonctionnera, 'p.class#id' générera une erreur).
     */
    final public function setTag(string $tag): void
    {
        $regexp =
            '~^
            (                       # Obligatoire : tag
                [a-z][a-z0-9-]*     # $1=tag
            )
            (?:                     # Optionnel : nom entre crochets
                \[                  # crochet ouvrant
                    ([a-z-]+)       # $2=name
                \]                  # crochet fermant
            )?
            (?:                     # Optionnel : id
                \#                  # Précédé du signe dièse
                ([a-z-]+)           # $3=id
            )?
            (?:                     # Optionnel : une ou plusieurs classes
                \.                  # Commence par un point
                ([a-z\.-]+)         # $4=toutes les classes
            )*
            ($)                     # capture bidon, garantit tout de $1 à $4
            ~ix';

        $match = null;
        if (! preg_match($regexp, $tag, $match)) {
            throw new InvalidArgumentException("Incorrect tag: $tag");
        }

        $this->tag = $match[1];
        $match[2] && $this->setAttribute('name', $match[2]);
        $match[3] && $this->setAttribute('id', $match[3]);
        $match[4] && $this->setAttribute('class', strtr($match[4], '.', ' '));
    }

    /**
     * Retourne le nom du tag.
     *
     * @return string
     */
    final public function getTag(): string
    {
        return $this->tag;
    }
}
