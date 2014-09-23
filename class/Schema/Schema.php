<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Core
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Schema;

use Serializable, JsonSerializable;
use InvalidArgumentException;
use Docalist\Type\Any;

/**
 * Un schéma permet de décrire la liste des champs qui composent une structure
 * de données.
 */
class Schema extends Any {
    /**
     * Construit un nouveau schéma.
     *
     * @param array $value Propriétés du schéma.
     *
     * @param $defaultNamespace Namespace en cours, utilisé pour résoudre les
     * noms de classes relatifs dans les champs.
     *
     * @throws InvalidArgumentException Si le schéma contient des erreurs.
     */
    public function __construct(array $value, $defaultNameSpace = '') {
        if (isset($value['fields'])) {
            $fields = [];
            foreach ($value['fields'] as $key => $field) {
                // Gère les raccourcis autorisés si $field est une chaine
                if (is_string($field)) {
                    // Champ de la forme : entier => nom
                    if (is_int($key)) {
                        $field = ['name' => $field];
                    }

                    // Champ de la forme : nom => type
                    else {
                        $field = ['name' => $key, 'type' => $field];
                    }
                }

                // Champ de la forme : nom => array(propriétés)
                elseif (is_string($key)) {
                    $field['name'] = $key;
                }

                // Compile
                $field = new Field($field, $defaultNameSpace);

                // Vérifie que le nom du champ est unique
                $name = $field->name();
                if (isset($this->fields[$name])) {
                    $msg = 'Field %s defined twice';
                    throw new InvalidArgumentException(sprintf($msg, $name));
                }

                // Stocke le champ
                $fields[$name] = $field;
            }
            $value['fields'] = $fields;
        }
        $this->value = $value;
    }

    /**
     * Retourne une propriété du schéma.
     *
     * @param string $name Nom de la propriété
     *
     * @return mixed
     */
    public function __call($name, $arguments) {
        return isset($this->value[$name]) ? $this->value[$name] : null;
    }

    /**
     * Retourne une propriété du schéma.
     *
     * @param string $name Nom de la propriété
     *
     * @return mixed
     */
    public function __get($name) {
        return isset($this->value[$name]) ? $this->value[$name] : null;
    }

    /**
     * Modifie une propriété du schéma.
     *
     * @param string $name Nom de la propriété
     * @param mixed $value Valeur de la propriété
     *
     * @return mixed
     */
    public function __set($name, $value) {
        if (is_null($value)) {
            unset($this->value[$name]);
        } else {
            $this->value[$name] = $value;
        }
    }

    public function __unset($name) {
        unset($this->value[$name]);
    }

    /**
     * Retourne la liste des champs.
     *
     * @return Field[]
     */
    public function fields() {
        return isset($this->value['fields']) ? $this->value['fields'] : [];
    }

    /**
     * Retourne le nom des champs.
     *
     * @return string[]
     */
    public function fieldNames() {
        return array_keys($this->fields());
    }

    /**
     * Retourne le schéma du champ indiqué.
     *
     * @param string $field Le nom du champ.
     *
     * @throws Exception Une exception est générée si le champ indiqué n'existe
     * pas.
     *
     * @return Field
     */
    public function field($field) {
        $fields = $this->fields();
        if (!isset($fields[$field])) {
            $msg = 'Field %s does not exist';
            throw new InvalidArgumentException(sprintf($msg, $field));
        }

        return $fields[$field];
    }

    /**
     * Indique si le schéma contient le champ indiqué.
     *
     * @param string $field Le nom du champ à tester.
     *
     * @return bool
     */
    public function has($field) {
        $fields = $this->fields();

        return isset($fields[$field]);
    }

    /**
     * Convertit le schéma en tableau.
     *
     * @return array
     */
    public function toArray() {
        $result = $this->value;
        if (isset($result['fields'])) {
            $t = [];
            foreach ($result['fields'] as $name => $field) {
                $field = $field->toArray();
                unset($field['name']);
                if (empty($field)) {
                    $t[] = $name;
                } elseif (count($field) === 1 && isset($field['type'])) {
                    $t[$name] = $field['type'];
                } else {
                    $t[$name] = $field;
                }
            }
            $result['fields'] = $t;

        }

        return $result;
    }

    /**
     * Fusionne le schéma actuel avec les données passées en paramètre.
     *
     * @param array $data
     */
    public function merge(array $data) {
        foreach($data as $key => $value) {
            if ($key === 'fields') {
                $this->mergeFields($value);
            } else {
                $this->value[$key] = $value;
                if (empty($this->value[$key])) {
                    unset($this->value[$key]);
                }
            }
        }
    }

    private function mergeFields(array $data) {
        $fields = $this->value['fields'];
        $result = [];
        foreach ($data as $name => $data) { // $name = ancien nom du champ

            // le champ existe déjà
            if (isset($fields[$name])) {
                $field = $fields[$name];
                $field->merge($data);
            }

            // nouveau champ
            else {
                $field = new Field($data);
            }

            // Vérifie que le nom du champ est unique
            $name = $field->name(); // nouveau nom si renomage
            if (isset($result[$name])) {
                $msg = 'Field %s defined twice';
                throw new InvalidArgumentException(sprintf($msg, $name));
            }

            $result[$name] = $field;
        }
        $this->value['fields'] = $result;
    }

    /**
     * Sérialise le schéma (implémentation de l'interface Serializable).
     *
     * @return string
     */
//     public function serialize() {
//         return serialize($this->toArray());
//     }

    /**
     * Désérialise le schéma (implémentation de l'interface Serializable).
     *
     * @param string $serialized
     */
//     public function unserialize($serialized) {
//         $this->__construct(unserialize($serialized));
//     }

    /**
     * Spécifie les données qui doivent être sérialisées en JSON
     * (implémentation de l'interface JsonSerializable).
     *
     * @return mixed
     */
//     public function jsonSerialize () {
//         return $this->toArray();
//     }

    /**
     * Retourne la valeur par défaut du schéma, c'est-à-dire un tableau
     * contenant la valeur par défaut de tous les champs qui ont une propriété
     * "default" dans le schéma.
     *
     * @return array
     */
    public function defaultValue() {
        $result = [];
        foreach($this->fields() as $name => $field) {
            if (! is_null($field->defaultValue())) {
                $result[$name] = $field->defaultValue(true);
            }
        }

        return $result;
    }
}