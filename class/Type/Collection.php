<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Type;

use Docalist\Type\Exception\InvalidTypeException;
use ArrayAccess, Countable, IteratorAggregate, ArrayIterator;
use InvalidArgumentException;

/**
 * Une collection de types.
 */
class Collection extends Any implements ArrayAccess, Countable, IteratorAggregate {
    static protected $default = [];

    /**
     * Le type des éléments de cette collection.
     *
     * Destiné à être surchargé par les classes descendantes (Any par défaut).
     *
     * @var string
     */
    static protected $type = 'Docalist\Type\Any';

    /**
     * Retourne le type (le nom de classe complet) des éléments de cette
     * collection.
     *
     * @return string
     */
    static public final function type() {
        return static::$type;
    }

    public function assign($value) {
        ($value instanceof Any) && $value = $value->value();
        if (! is_array($value)){
            throw new InvalidTypeException('array');
        }

        $this->value = [];
        foreach ($value as $item) {
            $this->offsetSet(null, $item);
        }

        return $this;
    }

    public function value() {
        $result = [];
        foreach($this->value as $item) {
            $result[] = $item->value();
        }
        return $result;
    }

    /**
     * Indique si un élément existe à la position indiquée.
     * (implémentation de l'interface ArrayAccess).
     *
     * @param int $offset
     *
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->value[$offset]);
    }

    /**
     * Retourne l'élément qui figure à la position indiquée.
     * (implémentation de l'interface ArrayAccess).
     *
     * @param int $offset
     *
     * @return Any
     *
     * @throws InvalidArgumentException Si la position indiquée n'est pas
     * valide.
     */
    public function offsetGet($offset) {
        if (isset($this->value[$offset])) {
            return $this->value[$offset];
        }

        $msg = __('Offset %s does not exist in the collection.', 'docalist-core');
        throw new InvalidArgumentException(sprintf($msg, $offset));
    }

    /**
     * Stocke un élément à la position indiquée.
     * (implémentation de l'interface ArrayAccess).
     *
     * @param int $offset Position à laquelle sera inséré l'élément, ou null
     * pour ajouter l'élément à la fin de la collection. Le paramètre offset est
     * ignoré si une clé a été définie dans le schéma de la collection. Dans ce
     * cas, c'est la clé de l'élément qui est utilisée comme position.
     *
     * @param mixed $value Les données de l'élément.
     */
    public function offsetSet ($offset, $value) {
        // Cas d'une collection typée (avec un schéma)
        if ($this->schema) {
            // Détermine le type des éléments de cette collection
            $type = $this->schema->type();

            // Si value est un objet du bon type, ok
            if ($value instanceof $type) {
                $item = $value;
            }

            // Sinon instancie l'élément
            else {
                if (is_a($type, 'Docalist\Type\Object', true)) {
                    $item = new $type($value); /* @var $item Object */

                    // Un objet a déjà un schéma, donc on ne peut pas lui fournir le notre
                    // On se contente de recopier les propriétés qu'il n'a pas (format, etc.)
                    $item->schema->value += $this->schema->value;
                } else {
                    $item = new $type($value, $this->schema);
                }
            }
        }

        // Cas d'une collection libre (sans schéma associé)
        else {
            // Si value est déjà un Type, ok
            if ($value instanceof Any) {
                $item = $value;
            }

            // Sinon, essaie de créer un Type à partir de la valeur
            else {
                $item = self::guessType($value);
            }
        }

        // Si c'est une collection à clé, ignore offset et utilise le sous-champ
        if ($this->schema && $key = $this->schema->key()) {
            $key = $item->$key();
            $this->value[$key] = $item;
        }

        // Collection sans clés
        else {
            if (is_null($offset)) {
                $this->value[] = $item;
            } else {
                $this->value[$offset] = $item;
            }
        }
    }

    /**
     * Supprime un élément à une position donnée.
     * (implémentation de l'interface ArrayAccess).
     *
     * @param int $offset
     */
    public function offsetUnset($offset) {
        unset($this->value[$offset]);
    }

    /**
     * Retourne le nombre d'éléments dans la collection.
     * (implémentation de l'interface Countable).
     *
     * @return int
     */
    public function count() {
        return count($this->value);
    }

    /**
     * Retourne un itérateur permettant de parcourir la collection.
     * (implémentation de l'interface IteratorAggregate).
     *
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->value);
    }

    /**
     * Retourne le premier élément de la collection et positionne l'itérateur
     * interne au début.
     *
     * @return mixed Le premier élément ou false si la collection est vide.
     */
    public function first() {
        return reset($this->value);
    }

    /**
     * Retourne le dernier élément de la collection et positionne l'itérateur
     * interne à la fin.
     *
     * @return mixed Le dernier élément ou false si la collection est vide.
     */
    public function last() {
        return end($this->value);
    }

    /**
     * Retourne la clé ou l'index de l'élément en cours.
     *
     * @return int|string La clé de l'élément en cours ou null s'il n'y a pas
     * d'élément courant.
     */

    public function key() {
        return key($this->value);
    }

    /**
     * Retourne l'élément en cours.
     *
     * @return mixed L'élément ou false si la collection est vide.
     */
    public function current() {
        return current($this->value);
    }

    /**
     * Avance l'itérateur interne à l'élément suivant.
     *
     * @return mixed L'élément suivant ou false s'il n'y a plus d'éléments.
     */
    public function next() {
        return next($this->value);
    }

    /**
     * Retourne les clés de la collection.
     *
     * @return string[]
     */
    public function keys() {
        return array_keys($this->value);
    }

    /**
     * Met à jour les clés de la collection.
     *
     * Pour une collection simple, les éléments de la colelction sont simplement
     * renumérotés de façon continue à partir de zéro. Si une clé a été définie
     * dans le schéma de la collection, la collection est recréée en utilisant
     * la clé de chacun des éléments.
     *
     * @return self $this
     */
    public function refreshKeys() {
        if ($this->schema && $key = $this->schema->key()) {
            $result = [];
            foreach($this->value as $item) {
                $result[$item->$key->value()] = $item;
            }
            $this->value = $result;
        } else {
            $this->value = array_values($this->value);
        }

        return $this;
    }

    public function __toString() {
        if (empty($this->value)) {
            return '[ ]';
        }

        $result = '[';
        self::$indent .= '    ';
        foreach($this->value as $key => $item) {
            $result .= PHP_EOL . self::$indent;
            is_string($key) && $result .= var_export($key, true) . ': ' ;
            $result .= $item->__toString();
        }
        self::$indent = substr(self::$indent, 0, -4);
        $result .= PHP_EOL . self::$indent . ']';

        return $result;
    }

    public function filterEmpty($strict = true) {
        foreach($this->value as $key => $item) { /* @var $item Any */
            if ($item->filterEmpty($strict)) {
                unset($this->value[$key]);
            }
        }

        return empty($this->value);
    }
}