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

use Docalist\Data\Schema\Schema;
use ArrayObject, ArrayIterator;
use InvalidArgumentException;

/**
 * Implémentation de base de l'interface SchemaBasedObjectInterface.
 */
abstract class SchemaBasedObject implements SchemaBasedObjectInterface {

    /**
     * Un cache contenant les schémas qu'on a déjà compilé.
     *
     * @var Schema[]
     */
    protected static $schemaCache;

    protected $fields = array();

    /**
     * Construit un nouvel objet à partir des données passées en paramètre.
     *
     * @param array $data Les données initiales de l'objet.
     */
    public function __construct(array $data = null) {
        ! is_null($data) && $this->fromArray($data);
    }

    /**
     * Retourne la valeur du champ indiqué.
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws InvalidArgumentException Si le champ indiqué n'existe pas dans le schéma.
     */
    public function __get($name) {
        // retourne le champ s'il existe déjà
        if (array_key_exists($name, $this->fields)) {
            return $this->fields[$name];
        }

        // Vérifie que le champ existe et récupère son schéma
        $field = $this->schema($name);

        // Initialise le champ avec sa valeur par défaut
        return $this->fields[$name] = $field->instantiate($field->defaultValue());
    }

    /**
     * Modifie la valeur du champ indiqué.
     *
     * @param string $name
     * @param string $value
     *
     * @throws InvalidArgumentException Si le champ indiqué n'existe pas dans le schéma.
     */
    public function __set($name, $value) {
        // Vérifie que le champ existe et récupère son schéma
        $field = $this->schema($name);

        // Attribuer null à un champ équivaut à unset()
        $value === null && $value = $field->defaultValue();

        // Stocke la valeur
        $this->fields[$name] = $field->instantiate($value);
    }

    public function __unset($name) {
        $field = $this->schema($name);
        $this->fields[$name] = $field->instantiate($field->defaultValue());
    }

    public function __isset($name) {
        return isset($this->fields[$name]);
    }

    public function count() {
        return count($this->fields);
    }

    public function serialize() {
        return serialize($this->fields);
    }

    public function unserialize($serialized) {
        $this->fields = unserialize($serialized);
    }

    public function jsonSerialize() {
        return $this->toArray();
    }

    public function getIterator() {
        return new ArrayIterator($this->fields);
    }

    public function fromArray(array $data) {
        $this->fields = array();

        foreach ($data as $field => $value) {
            $this->__set($field, $value);
        }
    }

    public function isEmpty() {
        foreach($this->fields as $key => $item) {
            if ($this->has($key)) {
                return false;
            }
        }

        return true;
    }

    public function has($name) {
        if (! isset($this->fields[$name])) {
            return false;
        }

        $item = $this->fields[$name];

        if (is_object($item) && $item->isEmpty()) {
            return false;
        }

        if (is_null($item) || $item === '') {
            return false;
        }

        return true;
    }

    public function toArray() {
        $result = [];
        foreach($this->fields as $key => $item) {
            is_object($item) && $item = $item->toArray();
            !empty($item) && $result[$key] = $item;
        }
        return $result;
    }

    public function __toString() {
        $result = '';
        foreach($this->fields as $name => $value) {
            $result .= $this->schema($name)->label();
            $result .= ' : ';
            $result .= $value;
            $result .= ' ';
        }
        return $result;
    }

    public function refresh() {
        foreach($this->fields as $field) {
            is_object($field) && $field->refresh();
        }
        return $this;
    }

    public function formatField($name, $format = null, $separator = ', ') {
        // Chaque champ peut avoir une méthode de la forme formatChamp()
        $formatter = 'format' . $name;

        // Si elle existe, c'est elle qui fait le boulot (appellée même si champ vide)
        if (method_exists($this, $formatter)) {
            return $this->$formatter($format);
        }

        // Retourne une chaine vide si le champ n'existe pas
        if (! isset($this->fields[$name])) {
            return '';
        }

        // Formatte les entités et les collections
        $field = $this->fields[$name];
        if (is_object($field)) {
            return $field->format($format, $separator);
        }

        // Caste les champs simples en chaine
        return (string) $field;
    }

    public function format($format = null, $separator = ', ') {
        $result = '';
        foreach($this->fields as $name => $field) {
            if ($this->has($name)) {
                $result .= $this->formatField($name);
            }
        }

        return $result;
    }
}