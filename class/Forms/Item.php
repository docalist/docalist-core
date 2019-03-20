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
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class Item
{
    /**
     * @var Container Le containeur parent de l'item.
     */
    protected $parent;

    /**
     * Crée un nouvel item.
     *
     * @param Container $parent Optionnel, le containeur parent de l'item.
     */
    public function __construct(Container $parent = null)
    {
        ! is_null($parent) && $parent->add($this);
    }

    /**
     * Constructeur statique.
     *
     * @return self
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Indique le type de l'item.
     *
     * Le type retourné correspond à la version en minuscules du dernier élément du nom de la classe PHP de l'item.
     *
     * Par exemple, la méthode retournera 'input' pour un élément de type {@link Input Docalist\Forms\Input}.
     *
     * @return string
     */
    final public function getType()
    {
        $class = get_class($this);
        $pos = strrchr($class, '\\');

        return strtolower($pos === false ? $class : substr($pos, 1));
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
     *
     * @return self
     */
    final public function setParent(Container $parent = null)
    {
        ! is_null($this->parent) && $this->parent->remove($this);
        ! is_null($parent) && $parent->add($this);

        return $this;
    }

    /**
     * Retourne le container parent de l'item ou null si l'élément ne figure pas dans un {@link Container}.
     *
     * @return Container
     */
    final public function getParent()
    {
        return $this->parent;
    }

    /**
     * Retourne le container racine de la hiérarchie.
     *
     * La méthode retourne le Container de plus haut niveau qui contient l'item.
     *
     * Si l'item ne figure pas dans un {@link Container} (s'il n'a pas de parent), elle retourne l'item lui-même.
     *
     * @return Container|Item
     */
    final public function getRoot()
    {
        return $this->parent ? $this->parent->getRoot() : $this;
    }

    /**
     * Retourne la profondeur à laquelle se trouve l'item dans la hiérarchie.
     *
     * La {@link getRoot racine de la hiérarchie} est au niveau 0, les items qu'il contient sont au niveau 1,
     * les items des items sont au niveau 2, et ainsi de suite.
     *
     * @return int
     */
    final public function getDepth()
    {
        return $this->parent ? 1 + $this->parent->getDepth() : 0;
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
     *
     * @return string
     */
    public function getPath($separator = '/')
    {
        return $this->parent ? $this->parent->getPath($separator) : '';
    }

    /**
     * Génère le code html de l'item et le retourne sous forme de chaine.
     *
     * @param Theme|string|null $theme Optionnel, le thème de formulaire à utiliser. Si vous n'indiquez pas
     * de thème, le thème par défaut est utilisé.
     *
     * @return string Le code html de l'item.
     */
    final public function render($theme = null)
    {
        return Theme::get($theme)->render($this);
    }

    /**
     * Génère le code html de l'item et l'affiche.
     *
     * @param Theme|string|null $theme
     *
     * @return self
     */
    final public function display($theme = null)
    {
        Theme::get($theme)->display($this);

        return $this;
    }

    /**
     * Indique si le container parent doit afficher un layout (bloc row) pour cet item.
     *
     * Par défaut, hasLayout() retourne false pour les items (ils sont affichés tels quels) et true pour les
     * éléments (le container génère un bloc label, un bloc description, etc.)
     *
     * @return bool
     */
    protected function hasLayout()
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
     *
     * @return bool
     */
    protected function hasLabelBlock()
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
     *
     * @return bool
     */
    protected function hasDescriptionBlock()
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
     *
     * @return bool
     */
    protected function isLabelable()
    {
        return true;
    }
}
