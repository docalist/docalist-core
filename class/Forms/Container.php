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

use ArrayIterator;
use Closure;
use Countable;
use Docalist\Forms\Traits\ItemFactoryTrait;
use Docalist\Schema\Schema;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Un container est un élément de formulaire qui peut contenir d'autres items.
 *
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
class Container extends Element implements Countable, IteratorAggregate
{
    use ItemFactoryTrait;

    /**
     * @var Item[] les items présents dans le container
     */
    protected $items = [];

    /**
     * Ajoute un item dans le container.
     *
     * @param Item $item L'item à ajouter
     *
     * @return Item L'item ajouté à la liste
     */
    final public function add(Item $item): Item
    {
        if ($item instanceof Container && $this->inTree($item)) {
            throw $this->invalidArgument('Circular reference detected in %s');
        }

        $item->parent = $this;
        $this->items[] = $item;

        return $item;
    }

    /**
     * Ajoute plusieurs items dans le container.
     *
     * @param Item[] $items
     */
    final public function addItems(array $items): void
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * Supprime un item du container.
     *
     * @param string|Item $item L'item à supprimer.
     *
     * Vous pouvez soit passer l'objet item à supprimer, soit son nom. Si vous passez un nom, tous les items
     * de la liste ayant ce nom seront supprimé.
     * @param string|Item $item
     */
    final public function remove($item): void
    {
        $isSame = $this->getComparator($item);

        foreach ($this->items as $index => $item) {
            if ($isSame($item)) {
                $item->parent = null;
                unset($this->items[$index]);
            }
        }
    }

    /**
     * Supprime tous les items du container.
     */
    final public function removeAll(): void
    {
        foreach ($this->items as $item) {
            $item->parent = null;
        }

        $this->items = [];
    }

    /**
     * Teste si l'item indiqué figure dans le container.
     *
     * @param string|Item $item L'item à tester (soit un objet Item, soit son nom)
     */
    final public function has($item): bool
    {
        return !is_null($this->get($item));
    }

    /**
     * Retourne un item.
     *
     * @param string|Item $item L'item à retourner (soit un objet Item, soit son nom)
     */
    final public function get($item): ?Item
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
     */
    final public function hasItems(): bool
    {
        return [] !== $this->items;
    }

    /**
     * Retourne la liste des items présents dans le container.
     *
     * @return Item[]
     */
    final public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Initialise la liste des items présents dans le container.
     *
     * @param Item[] $items
     */
    final public function setItems(array $items): static
    {
        $this->removeAll();
        $this->addItems($items);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final protected function getControlName(): string
    {
        // Un container sans nom retourne le nom de son container parent
        if ('' === $this->name) {
            return $this->parent instanceof Container ? $this->parent->getControlName() : '';
        }

        // Sinon, traitement standard
        return parent::getControlName();
    }

    /**
     * {@inheritDoc}
     */
    final protected function isLabelable(): bool
    {
        return false;
    }

    /**
     * Retourne le nombre d'items dans le container.
     *
     * (implémentation de l'interface Countable).
     */
    final public function count(): int
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
    final public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Teste si l'item passé en paramètre figure déjà dans la hiérarchie.
     *
     * Cette méthode est utilisée par add() pour détecter les références circulaires.
     */
    private function inTree(Container $item): bool
    {
        return ($item === $this) || ($this->parent && $this->parent->inTree($item));
    }

    /**
     * Retourne une Closure permettant de comparer l'item passé en paramètre avec un autre item.
     *
     * @param Item|string $item
     *
     * @throws InvalidArgumentException
     */
    private function getComparator($item): Closure
    {
        // Un item : true si c'est le même objet
        if ($item instanceof Item) {
            return fn (Item $other): bool => $other === $item;
        }

        // Une chaine : true si c'est le même nom
        if (is_string($item)) {
            return fn (Item $other): bool => ($other instanceof Element) && $other->getName() === $item;
        }

        throw new InvalidArgumentException('Bad comparator type');
    }

    /**
     * {@inheritDoc}
     */
    final protected function bindSchema(Schema $schema): void
    {
        parent::bindSchema($schema);

        foreach ($this->items as $item) {
            if ($item instanceof Element && ($name = $item->getName()) && $schema->hasField($name)) {
                $item->bindSchema($schema->getField($name));
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    final public function bindData($data): void
    {
        if (is_null($data)) {
            return;
        } // ???? quand on charge une grille, erreur pour othertitle, on a fields = null

        // Si le container est répétable, $data doit être un tableau (en général numérique : liste des valeurs)
        if ($this->isMultivalued()) {
            if (!is_array($data)) {
                throw $this->invalidArgument(
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
            if (!is_array($data)) {
                throw $this->invalidArgument(
                    'Value for "%s" must be a Composite or an array, got "%s"',
                    gettype($data)
                );
            }

            // Binde chacun des champs
            $result = [];
            foreach ($this->items as $item) {
                // Seuls les éléments peuvent avoir une valeur (i.e. pas les items, les tags, etc.)
                if (!$item instanceof Element) {
                    continue;
                }

                // Si l'élément a un nom, fait le binding sur cet élément
                $name = $item->getName();
                if ('' !== $name) {
                    $item->bindData($data[$name] ?? null);
                    $result[$name] = $item->getData();
                    continue;
                }

                // Elément sans nom. Si c'est un container, on lui passe toutes les données pour qu'il transmette
                if ($item instanceof Container) {
                    $item->bindData($data);
                    foreach ($item->getData() as $name => $value) {
                        $result[$name] = $value; // tester si existe déjà ?
                    }
                }
            }

            $this->data[$key] = $result;
        }

        // Supprime le tableau artificiel créé pour simplifier le code
        if (!$this->isMultivalued()) {
            $this->data = reset($this->data);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function setOccurence($occurence): void
    {
        parent::setOccurence($occurence);

        if ($this->isRepeatable()) {
            $data = $this->data[$occurence] ?? null;
            foreach ($this->items as $item) {
                // Seuls les éléments peuvent être avoir une valeur (i.e. pas les items, les tags, etc.)
                if (!$item instanceof Element) {
                    continue;
                }

                // Si l'élément a un nom, fait le binding sur cet élément
                $name = $item->getName();
                if ('' !== $name) {
                    $item->bindData($data[$name] ?? null);
                    continue;
                }

                // Elément sans nom. Si c'est un container, on lui passe toutes les données pour qu'il les transmette
                if ($item instanceof Container) {
                    $item->bindData($data);
                }
            }
        }
    }
}
