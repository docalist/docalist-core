<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Core
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Data\Entity;

use Docalist\Data\Schema\Field;
use ArrayObject;
use InvalidArgumentException;
use Docalist\Utils;
use ArrayAccess;
use ArrayIterator;

/**
 * Une propriété répétable.
 */
class Collection implements SchemaBasedObjectInterface, ArrayAccess {
    /**
     * Le schéma des éléments de la collection.
     *
     * @var Field
     */
    protected $schema;

    /**
     * Les données de la collection
     *
     * @var AbstractEntity[]
     */
    protected $items = [];

    /**
     * Construit une nouvelle collection.
     *
     * @param Field $schema Le schéma de la collection.
     *
     * @param array $data Les données initiales de la collection.
     */
    public function __construct(Field $schema, array $data = null) {
        // Stocke le schéma
        $this->schema = $schema;

        // Stocke les données
        $data && $this->fromArray($data);
    }

    public function schema($field = null) {
        return is_null($field) ? $this->schema : $this->schema->field($field);
    }

    public function count() {
        return count($this->items);
    }

    public function getIterator() {
        return new ArrayIterator($this->items);
    }

    public function serialize() {
        return serialize($this->items);
    }

    public function unserialize($serialized) {
        $this->items = unserialize($serialized);
    }

    public function jsonSerialize() {
        return $this->toArray();
    }

    public function offsetExists($offset) {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    public function offsetSet ($offset, $value) {
        $item = $this->schema->instantiate($value, true);

        // Si c'est une collection à clé, ignore offset et utilise le sous-champ
        if ($key = $this->schema->key()) {
            $this->items[$item->$key] = $item;
        }

        // Collection sans clés
        else {
            if (is_null($offset)) {
                $this->items[] = $item;
            } else {
                $this->items[$offset] = $item;
            }
        }
    }

    public function offsetUnset($offset) {
        unset($this->items[$offset]);
    }

    public function fromArray(array $data) {
        $this->items = [];

        foreach($data as $item) {
            $this->offsetSet(null, $item);
        }
    }

    public function toArray() {
        $result = [];
        foreach($this->items as $key => $item) {
            is_object($item) && $item = $item->toArray();
            !empty($item) && $result[$key] = $item;
        }
        return $result;
    }

    public function refresh() {
        // Si c'est une collection sans clés, rien à faire
        if (! $key = $this->schema->key()) {
            return;
        }

        // Reconstruit les clés
        $result = [];
        foreach($this->items as $item) {
            is_object($item) && $item->refresh();
            $result[$item->$key] = $item;
        }
        $this->items = $result;

        return $this;
    }

    public function __toString() {
        // Collection d'objets
        if ($this->schema->type() ==='object') {
            return implode('<br />', $this->items);
        }

        // Collection de scalaires
        return implode(' ¤ ', $this->items);
    }

    /**
     * Retourne le premier élément de la collection et positionne l'itérateur
     * interne au début.
     *
     * @return mixed
     */
    public function first() {
        return reset($this->items);
    }

    /**
     * Retourne le dernier élément de la collection et positionne l'itérateur
     * interne à la fin.
     *
     * @return mixed
     */
    public function last() {
        return end($this->items);
    }

    /**
     * Retourne la clé ou l'index de l'élément en cours.
     *
     * @return int|string
     */

    public function key() {
        return key($this->items);
    }

    /**
     * Retourne l'élément en cours.
     *
     * @return mixed
     */
    public function current() {
        return current($this->items);
    }

    /**
     * Avance l'itérateur interne à l'élément suivant.
     *
     * @return mixed
     */
    public function next() {
        return next($this->items);
    }

    public function isEmpty() {
        return !$this->items;
    }

    public function format($format = null, $separator = ', ') {
        $t = [];
        foreach($this->items as $item) {
            $t[] = is_object($item) ? $item->format($format, $separator) : (string) $item;
        }

        return implode($separator, $t);
    }
}