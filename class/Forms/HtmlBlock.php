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
 * Un bloc Html est un item de formulaire dont le contenu est envoyé tel quel lorsque le formulaire est généré.
 *
 * La classe HtmlBlock est la classe de base pour les items de formulaire qui ne sont pas gérés par
 * docalist-forms (i.e. ils n'ont pas de nom, de label ou de description, ils n'ont pas de valeur, ils ne
 * participent pas au binding, etc.)
 *
 * Cela permet d'insérer du code "brut" au sein d'un formulaire :
 * - du {@link HtmlBlock code html},
 * - du {@link Text texte},
 * - des {@link Comment commentaires}.
 * - des {@link Tag tags},
 * - etc.
 *
 * Propriétés principales :
 * - c'est un item
 * - possède un contenu
 *
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
class HtmlBlock extends Item
{
    /**
     * Le contenu du bloc html.
     */
    protected string $content;

    /**
     * Crée un nouvel item.
     *
     * @param string         $content optionnel, le contenu du bloc html
     * @param Container|null $parent  optionnel, le containeur parent de cet item
     */
    public function __construct(string $content = '', Container $parent = null)
    {
        parent::__construct($parent);
        $this->setContent($content);
    }

    /**
     * Définit le contenu du bloc html.
     */
    final public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * Retourne le contenu du bloc html.
     */
    final public function getContent(): string
    {
        return $this->content;
    }
}
