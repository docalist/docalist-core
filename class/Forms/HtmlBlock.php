<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
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
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class HtmlBlock extends Item
{
    /**
     * @var string Le contenu du bloc html.
     */
    protected $content;

    /**
     * Crée un nouvel item.
     *
     * @param string    $content    Optionnel, le contenu du bloc html.
     * @param Container $parent     Optionnel, le containeur parent de cet item.
     */
    public function __construct($content = null, Container $parent = null)
    {
        parent::__construct($parent);
        $this->setContent($content);
    }

    /**
     * Définit le contenu du bloc html.
     *
     * @param string $content
     *
     * @return self
     */
    public function setContent($content)
    {
        $this->content = ($content === '' || $content === false) ? null : $content;

        return $this;
    }

    /**
     * Retourne le contenu du bloc html.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}
