<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Docalist\Data\Schema;

use Docalist\Data\Entity\Collection;
use InvalidArgumentException;

/**
 * Décrit les propriétés d'un champ au sein d'un schéma.
 */
class Field {
    /**
     * Nom du champ.
     *
     * @var string
     */
    protected $name;

    /**
     * Type du champ
     *
     * @var string
     */
    protected $type;

    /**
     * Nom de classe complet de l'entité.
     *
     * @var string
     */
    protected $entity;

    /**
     * Indique si le champ est répétable.
     *
     * @var boolean
     */
    protected $repeatable;

    /**
     * Valeur par défaut du champ.
     *
     * @var mixed
     */
    protected $default;

    /**
     * Libellé du champ.
     *
     * @var string
     */
    protected $label;

    /**
     * Description du champ.
     *
     * @var string
     */
    protected $description;

    /**
     * Nom du sous-champ utilisé comme clé si le champ est une collection
     * d'entités,
     *
     * @var string
     */
    protected $key;

    /**
     * Liste des types de champs reconnus et valeur par défaut.
     *
     * @var array
     */
    // @formatter:off
    protected static $fieldTypes = [
        'string' => '',
        'int' => 0,
        'long' => 0,
        'bool' => false,
        'float' => 0.0,
        'object' => array(),
    ];
    // @formatter:on

    public function __construct(array $data) {
        // Teste si le champ contient des propriétés qu'on ne connait pas
        if ($unknown = array_diff_key($data, get_object_vars($this))) {
            $msg = 'Unknown field property(es) in field "%s": "%s"';
            $name = isset($data['name']) ? $data['name'] : '';
            throw new InvalidArgumentException(sprintf($msg, $name, implode(', ', array_keys($unknown))));
        }

        // Nom
        $this->setName(isset($data['name']) ? $data['name'] : null);

        // Type
        $this->setType(isset($data['type']) ? $data['type'] : 'string');

        // Repeatable
        $this->setRepeatable(isset($data['repeatable']) ? $data['repeatable'] : $this->repeatable);

        // Default
        $this->setDefaultValue(isset($data['default']) ? $data['default'] : null);

        // Entity
        $this->setEntity(isset($data['entity']) ? $data['entity'] : null);

        // Key
        $this->setKey(isset($data['key']) ? $data['key'] : null);

        // Label
        $this->setLabel(isset($data['label']) ? $data['label'] : null);

        // Description
        $this->setDescription(isset($data['description']) ? $data['description'] : null);
    }

    /**
     * Initialise le nom du champ.
     *
     * @param string $name
     *
     * @throws InvalidArgumentException
     */
    protected function setName($name) {
        // Le nom du champ est obligatoire
        if (empty($name)) {
            $msg = 'Field must have a name';
            throw new InvalidArgumentException(sprintf($msg));
        }

        // Le nom de champ ne doit contenir que des lettres et des chiffres
        if (!ctype_alnum($name)) {
            $msg = 'Invalid field name "%s": must contain only letters';
            throw new InvalidArgumentException(sprintf($msg, $name));
        }

        $this->name = $name;
    }

    /**
     * Retourne le nom du champ.
     *
     * @return string
     */
    public function name() {
        return $this->name;
    }

    /**
     * Initialise le type du champ.
     *
     * @param string $type
     */
    protected function setType($type) {
        // type='xx*' équivaut à type='xx' + repeatable=true
        if (substr($type, -1) === '*') {
            $type = substr($type, 0, -1);
            $this->repeatable = true;
        }

        // Teste s'il s'agit d'un type simple (int, string, etc.)
        if (array_key_exists($type, self::$fieldTypes)) {
            $this->type = $type;

            return;
        }

        // Type est une entité
        $this->type = 'object';
        $this->setEntity($type);
    }

    /**
     * Retourne le type du champ.
     *
     * @return string
     */
    public function type() {
        return $this->type;
    }

    /**
     * Définit si le champ est répétable.
     *
     * @param bool $repeatable
     */
    protected function setRepeatable($repeatable) {
        $this->repeatable = (bool) $repeatable;
    }

    /**
     * Indique si le champ est répétable.
     *
     * @return bool
     */
    public function repeatable() {
        return $this->repeatable;
    }

    /**
     * Définit le sous-champ utilisé comme clé dans la collection.
     *
     * @param string $key
     * @throws InvalidArgumentException
     */
    protected function setKey($key) {
        if (! is_null($key)) {
            if (! $this->repeatable) {
                $msg = 'property "key" is not allowed for field "%s" (field is not repeatable)';
                throw new InvalidArgumentException(sprintf($msg, $this->name));
            }

            if (! $this->entity) {
                $msg = 'property "key" is not allowed for field "%s" (field is not an entity)';
                throw new InvalidArgumentException(sprintf($msg, $this->name));
            }
        }
        $this->key = $key;
    }

    /**
     * Pour une collection d'entités, indique le sous-champ utilisé comme clé
     * pour les entrées de la collection.
     *
     * @return string|null
     */
    public function key() {
        return $this->key;
    }

    /**
     * Définit la valeur par défaut du champ.
     *
     * @param mixed $default
     * @throws InvalidArgumentException
     */
    protected function setDefaultValue($default) {
        if (! is_null($default)) {
            if ($this->type === 'object') {
                if ($ok = is_array($default)) {
                    $keys = array_keys($default);
                    if ($this->repeatable) {
                        // on attend une liste d'entités
                        $expected = 'array of entities (array of numerical arrays)';
                        $ok = count(array_filter($keys, 'is_int')) === count($keys);
                    } else {
                        // on attend une liste de champs
                        $expected = 'entity (associative array)';
                        $ok = count(array_filter($keys, 'is_string')) === count($keys);
                    }
                }
            } else {
                $is = "is_" . $this->type;
                if ($this->repeatable) {
                    $expected = 'numerical array of ' . $this->type;
                    // on doit avoir un tableau
                    if ($ok = is_array($default)) {

                        // Les clés doivent être numériques
                        $keys = array_keys($default);
                        $ok = count(array_filter($keys, 'is_int')) === count($keys);

                        // Toutes les valeurs doivent être du même type que le champ
                        $ok && $ok = count(array_filter($default, $is)) === count($keys);
                    }
                } else {
                    $expected = $this->type;
                    $ok = $is($default);
                }
            }

            if (! $ok) {
                $msg = 'Bad default value for field "%s": expected %s';
                throw new InvalidArgumentException(sprintf($msg, $this->name, $expected));
            }
        }
        $this->default = $default;
    }

    /**
     * Retourne la valeur par défaut du champ.
     *
     * @return mixed
     */
    public function defaultValue() {
        if (!is_null($this->default)) {
            return $this->default;
        }

        return $this->repeatable ? array() : self::$fieldTypes[$this->type];
    }

    /**
     * Définit l'entité du champ.
     *
     * @param string $entity
     * @throws InvalidArgumentException
     */
    protected function setEntity($entity) {
        if (empty($entity)) {
            if ($this->type === 'object') {
                $entity = 'Docalist\Data\Object';
            }
            return;
        }

        // Seuls les champs objets peuvent avoir une entité
        if ($this->type !== 'object') {
            $msg = 'Field "%s" can not have an entity property: not an object';
            throw new InvalidArgumentException(sprintf($msg, $this->name));
        }

        // Vérifie que la classe indiquée existe
        if (! class_exists($entity)) {
            $msg = 'Invalid entity type "%s" for field "%s": class not found';
            throw new InvalidArgumentException(sprintf($msg, $entity, $this->name));
        }

        // Vérifie que la classe est une entité
        if (! is_a($entity, 'Docalist\Data\Entity\EntityInterface', true)) {
            $msg = 'Invalid entity type "%s" for field "%s": not an EntityInterface';
            throw new InvalidArgumentException(sprintf($msg, $entity, $this->name));
        }

        $this->entity = $entity;
    }

    /**
     * Indique le nom de la classe à utiliser pour représenter les données du
     * champ.
     *
     * @return string
     */
    public function entity() {
        return $this->entity;
    }

    /**
     * Définit le libellé du champ.
     *
     * @param string $label
     */
    protected function setLabel($label) {
        $this->label = $label;
    }

    /**
     * Retourne le libellé du champ, ou son nom si le champ n'a pas de libellé.
     *
     * @return string
     */
    public function label() {
        return $this->label ?: $this->name;
    }

    /**
     * Définit la description du champ.
     *
     * @param string $description
     */
    protected function setDescription($description) {
        $this->description = $description;
    }

    /**
     * Retourne la description du champ.
     *
     * @return string
     */
    public function description() {
        return $this->description;
    }

    /**
     * Convertit le schéma en tableau.
     *
     * @return array
     */
    public function toArray() {
        $result = [];
        foreach ($this as $name => $value) {
            $value && $result[$name] = $value;
        }

        return $result;
    }

    /**
     * Crée une nouvelle instance du champ.
     *
     * @param scalar|array $value
     * @param boolean $single
     *
     * @return Collection|EntityInterface|scalar
     */
    public function instantiate($value = null, $single = false) {
        is_null($value) && $value = $this->defaultValue();

        if (! $single && $this->repeatable) {
            return new Collection($this, $value);
        }

        // Une entité
        if ($this->type === 'object') {
            // Value est déjà un objet. Vérifie que c'est le bon type
            if (is_object($value)) {
                $class = get_class($value);
                if (! is_a($this->entity, $class, true)) {
                    $msg = 'Invalid value "%s" for field "%s": expected "%s"';
                    throw new InvalidArgumentException(sprintf($msg, $class, $this->name, $this->entity));
                }
            }

            // Value doit être un tableau
            else {
                $value = new $this->entity($value);
            }
        }

        return $value;
    }
}