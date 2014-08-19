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

/**
 * Un schéma permet de décrire la liste des champs qui composent une structure
 * de données.
 */
class Schema implements Serializable, JsonSerializable {
    /**
     * La liste des champs du schéma.
     *
     * @var Field[]
     */
    protected $fields;

    /**
     * Construit un nouveau schéma.
     *
     * @param array $data La liste des champs du schéma.
     *
     * @param $defaultNamespace Namespace en cours, utilisé pour résoudre les
     * noms de classes relatifs dans les champs.
     *
     * @throws InvalidArgumentException Si le schéma contient des erreurs.
     */
    public function __construct(array $fields, $defaultNameSpace = '') {
        $this->fields = [];
        foreach ($fields as $key => $field) {
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
            $this->fields[$name] = $field;
        }
    }

    /**
     * Retourne la liste des champs.
     *
     * @return Field[]
     */
    public function fields() {
        return $this->fields;
    }

    /**
     * Retourne le nom des champs.
     *
     * @return string[]
     */
    public function fieldNames() {
        return $this->fields ? array_keys($this->fields) : [];
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
        if (!isset($this->fields()[$field])) {
            $msg = 'Field %s does not exist';
            throw new InvalidArgumentException(sprintf($msg, $field));
        }

        return $this->fields[$field];
    }

    /**
     * Indique si le schéma contient le champ indiqué.
     *
     * @param string $field Le nom du champ à tester.
     *
     * @return bool
     */
    public function has($field) {
        return isset($this->fields()[$field]);
    }

    /**
     * Convertit le schéma en tableau.
     *
     * @return array
     */
    public function toArray() {
        $result = [];
        foreach ($this->fields() as $name => $field) {
            $field = $field->toArray();
            unset($field['name']);
            if (isset($field['repeatable'])) {
                $field['type'] = isset($field['type']) ? ($field['type'] . '*') : 'string*';
                unset($field['repeatable']);
            }
            if (empty($field)) {
                $result[] = $name;
            } elseif (count($field) === 1 && isset($field['type'])) {
                $result[$name] = $field['type'];
//             } elseif (count($field) === 2 && isset($field['type']) && isset($field['repeatable'])) {
//                 $result[$name] = $field['type'] . '*';
            } else {
                $result[$name] = $field;
            }
        }

        return $result;
    }

    /**
     * Sérialise le schéma (implémentation de l'interface Serializable).
     *
     * @return string
     */
    public function serialize() {
        return serialize($this->toArray());
    }

    /**
     * Désérialise le schéma (implémentation de l'interface Serializable).
     *
     * @param string $serialized
     */
    public function unserialize($serialized) {
        $this->__construct(unserialize($serialized));
    }

    /**
     * Spécifie les données qui doivent être sérialisées en JSON
     * (implémentation de l'interface JsonSerializable).
     *
     * @return mixed
     */
    public function jsonSerialize () {
        return $this->toArray();
    }

    /**
     * Retourne la valeur par défaut du schéma, c'est-à-dire un tableau
     * contenant la valeur par défaut de tous les champs qui ont une propriété
     * "default" dans le schéma.
     *
     * @return array
     */
    public function defaultValue() {
        $result = [];
        foreach($this->fields as $name => $field) {
            if (! is_null($field->defaultValue())) {
                $result[$name] = $field->defaultValue(true);
            }
        }

        return $result;
    }
}