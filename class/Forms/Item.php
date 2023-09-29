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
 * Un item au sein d'un formulaire.
 *
 * Item est la classe de base (abstraite) de tout ce que peut contenir un formulaire :
 *
 * - des littéraux (bloc de texte, bloc html, commentaire...)
 * - des éléments de formulaire (input, textarea, fieldset...)
 *
 * Caractéristiques :
 * - peut avoir un parent
 * - dispose d'un type
 * - peut être affiché (render)
 *
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
abstract class Item
{
    /**
     * @var Container|null le containeur parent de l'item
     */
    protected ?Container $parent = null;

    /**
     * Crée un nouvel item.
     *
     * @param Container|null $parent optionnel, le containeur parent de l'item
     */
    public function __construct(Container $parent = null)
    {
        if (!is_null($parent)) {
            $parent->add($this);
        }
    }

    /**
     * Indique le type de l'item.
     *
     * Le type retourné correspond à la version en minuscules du dernier élément du nom de la classe PHP de l'item.
     *
     * Par exemple, la méthode retournera 'input' pour un élément de type {@link Input Docalist\Forms\Input}.
     */
    final public function getType(): string
    {
        $class = static::class;
        $pos = strrchr($class, '\\');

        return strtolower(false === $pos ? $class : substr($pos, 1));
    }

    /**
     * Modifie le container parent de l'item.
     *
     * Définir le parent d'un item revient à ajouter cet item à la fin du container indiqué.
     *
     * Si l'item avait déjà un parent, l'item est transféré du container existant vers le container indiqué.
     *
     * Si vous passez null en paramétre, l'item est supprimé de son container parent (s'il en avait un) et
     * devient un item autonome (sans parent).
     */
    final public function setParent(Container $parent = null): static
    {
        if (!is_null($this->parent)) {
            $this->parent->remove($this);
        }
        if (!is_null($parent)) {
            $parent->add($this);
        }

        return $this;
    }

    /**
     * Retourne le container parent de l'item ou null si l'élément ne figure pas dans un {@link Container}.
     */
    final public function getParent(): ?Container
    {
        return $this->parent;
    }

    /**
     * Retourne l'item racine de la hiérarchie.
     *
     * La méthode retourne le Container de plus haut niveau qui contient l'item.
     *
     * Si l'item ne figure pas dans un {@link Container} (s'il n'a pas de parent), elle retourne l'item lui-même.
     */
    final public function getRoot(): Item
    {
        return $this->parent instanceof Container ? $this->parent->getRoot() : $this;
    }

    /**
     * Retourne la profondeur à laquelle se trouve l'item dans la hiérarchie.
     *
     * La {@link getRoot racine de la hiérarchie} est au niveau 0, les items qu'il contient sont au niveau 1,
     * les items des items sont au niveau 2, et ainsi de suite.
     */
    final public function getDepth(): int
    {
        return $this->parent instanceof Container ? 1 + $this->parent->getDepth() : 0;
    }

    /**
     * Retourne le path complet de l'item.
     *
     * Le path est construit en concaténant le nom des éléments de la hiérarchie de la racine à l'élément
     * en cours. Seuls les éléments qui ont un nom apparaissent dans le path retourné.
     *
     * @parameter string $separator Séparateur à utiliser pour délimiter chaque niveau.
     *
     * Remarque : le séparateur est ajouté entre les noms, pas au début ni à la fin (par exemple, on
     * obtiendra 'person/name', pas '/person/name' ou 'person/name/').
     */
    public function getPath(string $separator = '/'): string
    {
        // pas "final" car surchargée dans Element (qui la rend "final")
        return $this->parent instanceof Container ? $this->parent->getPath($separator) : '';
    }

    /**
     * Génère le code html de l'item et le retourne sous forme de chaine.
     *
     * @param Theme|string|null $theme Optionnel, le thème de formulaire à utiliser. Si vous n'indiquez pas
     *                                 de thème, le thème par défaut est utilisé.
     *
     * @return string le code html de l'item
     */
    final public function render($theme = null): string
    {
        return Theme::get($theme)->render($this);
    }

    /**
     * Génère le code html de l'item et l'affiche.
     *
     * @param Theme|string|null $theme
     */
    final public function display($theme = null): static
    {
        Theme::get($theme)->display($this);

        return $this;
    }

    /**
     * Indique si le container parent doit afficher un layout (bloc row) pour cet item.
     *
     * Par défaut, hasLayout() retourne false pour les items (ils sont affichés tels quels) et true pour les
     * éléments (le container génère un bloc label, un bloc description, etc.)
     */
    protected function hasLayout(): bool
    {
        return false;
    }

    /**
     * Indique si le container parent doit afficher un bloc label pour cet item.
     *
     * Par défaut, hasLabelBlock() retourne false pour les items (car ils n'ont pas de label) et true
     * pour les éléments.
     *
     * Certains éléments surchargent cette méthode lorsqu'ils souhaitent gérer eux-mêmes l'affichage de
     * leur libellé (Button par exemple).
     */
    protected function hasLabelBlock(): bool
    {
        return false;
    }

    /**
     * Indique si le container parent doit afficher un bloc description pour cet item.
     *
     * Par défaut, hasDescriptionBlock retourne false pour les items (car ils n'ont pas de description) et
     * true pour les éléments.
     *
     * Certains éléments surchargent cette méthode lorsqu'ils souhaitent gérer eux-mêmes l'affichage de leur
     * description (Checkbox par exemple).
     */
    protected function hasDescriptionBlock(): bool
    {
        return false;
    }

    /**
     * Indique si l'item peut avoir un label associé.
     *
     * Par exemples, un élément Checklist n'est pas labelable (car il est représenté par un tag <ul>),
     * ni un container.
     *
     * cf. https://developer.mozilla.org/fr/docs/Web/HTML/Cat%C3%A9gorie_de_contenu
     */
    protected function isLabelable(): bool
    {
        return true;
    }
}
