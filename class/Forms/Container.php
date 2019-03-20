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

use Countable;
use IteratorAggregate;
use ArrayIterator;
use Docalist\Forms\Traits\ItemFactoryTrait;
use Closure;
use Docalist\Schema\Schema;
use InvalidArgumentException;

/**
 * Un container est un élément de formulaire qui peut contenir d'autres items.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Container extends Element implements Countable, IteratorAggregate
{
    use ItemFactoryTrait;

    /**
     * @var Item[] Les items présents dans le container.
     */
    protected $items = [];

    /**
     * Ajoute un item dans le container.
     *
     * @param Item $item L'item à ajouter.
     *
     * @return Item L'item ajouté à la liste.
     */
    public function add(Item $item)
    {
        if ($item instanceof self && $this->inTree($item)) {
            return $this->invalidArgument('Circular reference detected in %s');
        }

        $item->parent = $this;
        $this->items[] = $item;

        return $item;
    }

    /**
     * Ajoute plusieurs items dans le container.
     *
     * @param Item[] $items
     *
     * @return self
     */
    public function addItems(array $items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }

        return $this;
    }

    /**
     * Supprime un item du container.
     *
     * @param string|Item $item L'item à supprimer.
     *
     * Vous pouvez soit passer l'objet item à supprimer, soit son nom. Si vous passez un nom, tous les items
     * de la liste ayant ce nom seront supprimé.
     *
     * @param string|Item $item
     *
     * @return self
     */
    public function remove($item)
    {
        $isSame = $this->getComparator($item);

        foreach ($this->items as $index => $item) {
            if ($isSame($item)) {
                $item->parent = null;
                unset($this->items[$index]);
            }
        }

        return $this;
    }

    /**
     * Supprime tous les items du container.
     *
     * @return self
     */
    public function removeAll()
    {
        foreach ($this->items as $item) {
            $item->parent = null;
        }

        $this->items = [];

        return $this;
    }

    /**
     * Teste si l'item indiqué figure dans le container.
     *
     * @param string|Item $item L'item à tester (soit un objet Item, soit son nom).
     *
     * @return bool
     */
    public function has($item)
    {
        return !is_null($this->get($item));
    }

    /**
     * Retourne un item
     *
     * @param string|Item $item L'item à retourner (soit un objet Item, soit son nom).
     *
     * @return Item|null
     */
    public function get($item)
    {
        $isSame = $this->getComparator($item);

        foreach ($this->items as $item) {
            if ($isSame($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Teste si le container contient des items.
     *
     * @return bool
     */
    public function hasItems()
    {
        return !empty($this->items);
    }

    /**
     * Retourne la liste des items présents dans le container.
     *
     * @return Item[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Initialise la liste des items présents dans le container.
     *
     * @param Item[] $items
     *
     * @return self
     */
    public function setItems(array $items)
    {
        return $this->removeAll()->addItems($items);
    }

    /**
     * Retourne le nom du contrôle html du container.
     *
     * La nom du contrôle est construit à partir du nom du nom du container, de son numéro d'occurence (s'il est
     * répétable) et du nom de son container parent éventuel.
     *
     * Par exemple, si on a un container "livraison" dans un container "addresses" répétable, la méthode
     * retournera une chaine de la forme : "adresses[i][livraison]".
     *
     * Important : si le conteneur n'a pas de nom, la méthode retourne le nom de son container parent.
     * Cela permet à un container sans nom d'être "neutre". On peut ainsi ajouter des containers intermédiaires
     * (sans nom) dans un formulaire (pour l'UI ou pour regrouper certains champs par exemple), sans que cela
     * influe sur le nom des contrôles générés.
     *
     * @return string
     */
    protected function getControlName()
    {
        // Un container sans nom retourne le nom de son container parent
        if (is_null($this->name)) {
            return $this->parent ? $this->parent->getControlName() : '';
        }

        // Sinon, traitement standard
        return parent::getControlName();
    }

    protected function isLabelable()
    {
        return false;
    }

    /**
     * Retourne le nombre d'items dans le container.
     *
     * (implémentation de l'interface Countable).
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Retourne un itérateur permettant de parcourir la liste des items présents dans le container.
     *
     * (implémentation de l'interface IteratorAggregate).
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Teste si l'item passé en paramètre figure déjà dans la hiérarchie.
     *
     * Cette méthode est utilisée par add() pour détecter les références circulaires.
     *
     * @param Container $item
     * @return bool
     */
    private function inTree(Container $item)
    {
        return ($item === $this) || ($this->parent && $this->parent->inTree($item));
    }

    /**
     * Retourne une Closure permettant de comparer l'item passé en paramètre avec un autre item.
     *
     * @param Item|string $item
     *
     * @return Closure
     *
     * @throws InvalidArgumentException
     */
    private function getComparator($item)
    {
        // Un item : true si c'est le même objet
        if ($item instanceof Item) {
            return function (Item $other) use ($item) {
                return $other === $item;
            };
        }

        // Une chaine : true si c'est le même nom
        if (is_string($item)) {
            return function (Item $other) use ($item) {
                return ($other instanceof Element) && $other->getName() === $item;
            };
        }

        throw new InvalidArgumentException('Bad comparator type');
    }

    protected function bindSchema(Schema $schema = null)
    {
        parent::bindSchema($schema);

        foreach ($this->items as $item) {
            if ($item instanceof Element && ($name = $item->getName()) && $schema->hasField($name)) {
                $item->bindSchema($schema->getField($name));
            }
        }
    }

    public function bindData($data)
    {
        if (is_null($data)) {
            return;
        } // ???? quand on charge une grille, erreur pour othertitle, on a fields = null

        // Si le container est répétable, $data doit être un tableau (en général numérique : liste des valeurs)
        if ($this->isMultivalued()) {
            if (! is_array($data)) {
                return $this->invalidArgument(
                    'Container "%s" is repeatable, expected Collection or array, got "%s"',
                    gettype($data)
                );
            }
        } else {
            $data = [$data]; // ramène au cas unique tableau, ce qui simplifie le code ci-dessous
        }

        $this->data = [];
        foreach ($data as $key => $data) {
            // Chaque valeur du container doit être un un tableau associatif (liste des champs)
            if (! is_array($data)) {
                return $this->invalidArgument(
                    'Value for "%s" must be a Composite or an array, got "%s"',
                    gettype($data)
                );
            }

            // Binde chacun des champs
            $result = [];
            foreach ($this->items as $item) {
                // Seuls les éléments peuvent être avoir une valeur (i.e. pas les items, les tags, etc.)
                if (! $item instanceof Element) {
                    continue;
                }

                // Si l'élément a un nom, fait le binding sur cet élément
                if ($name = $item->getName()) {
                    $item->bindData(isset($data[$name]) ? $data[$name] : null);
                    $result[$name] = $item->getData();
                    continue;
                }

                // Elément sans nom. Si c'est un container, on lui passe toutes les données pour qu'il transmette
                if ($item instanceof self) {
                    $item->bindData($data);
                    foreach ($item->getData() as $name => $value) {
                        $result[$name] = $value; // tester si existe déjà ?
                    }
                }
            }

            $this->data[$key] = $result;
        }

        // Supprime le tableau artificiel créé pour simplifier le code
        if (! $this->isMultivalued()) {
            $this->data = reset($this->data);
        }

        return $this;
    }

    protected function setOccurence($occurence)
    {
        parent::setOccurence($occurence);

        if ($this->isRepeatable()) {
            $data = $this->data[$occurence];
            foreach ($this->items as $item) {
                // Seuls les éléments peuvent être avoir une valeur (i.e. pas les items, les tags, etc.)
                if (! $item instanceof Element) {
                    continue;
                }

                // Si l'élément a un nom, fait le binding sur cet élément
                if ($name = $item->getName()) {
                    $item->bindData(isset($data[$name]) ? $data[$name] : null);
                    continue;
                }

                // Elément sans nom. Si c'est un container, on lui passe toutes les données pour qu'il les transmette
                ($item instanceof self) && $item->bindData($data);
            }
        }

        return $this;
    }
}
