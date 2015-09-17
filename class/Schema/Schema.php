<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Core
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Schema;

use InvalidArgumentException;
use Docalist\Type\Any;

/**
 * Un schéma permet de décrire les attributs d'un {@link Docalist\Type\Any type
 * de données Docalist}.
 *
 * Pour un {@link Docalist\Type\Object type structuré}, un schéma permet
 * également de définir la liste des champs qui composent cette structure en
 * utilisant la collection fields.
 *
 * Les attributs d'un schéma peuvent être manipulés comme s'il s'agissait de
 * propriétés ou de méthodes du schéma :
 *
 * <code>
 *     $schema->description = 'Un exemple de schéma';
 *     echo $schema->description;       // propriété
 *     echo $schema->description();     // méthode
 * </code>
 */
class Schema extends Any {

/*
    Réflexion : bootstraper ?

    Actuellement, les propriétés du schéma sont stockées sous la forme de types
    php natifs et fields est un simple tableau php.

    Pour être cohérent il faudrait les stocker sous la forme de types docalist
    et non de types php : fields serait une Collection de Field et les
    propriétés scalaires (description, label...) seraient stockées avec le type
    docalist adéquat (Text, etc.)

    Cela simplifierait pas mal les choses, car dans ce cas, la classe Schema
    serait juste un objet standard (au lieu d'hériter de Any, on hériterait de
    Object). :

    - Les méthodes __get, __set, __isset, __unset,  __call pourraient être
      supprimées (déjà implémentées par Object).
    - La méthode field() pourrait être supprimée (propriété comme une autre)
    - $schema->fieldNames() pourrait être remplacé par $schema->fields->keys()
    - $schema->field($field) pourrait être remplacé par $schema->field[$field]
    - has($field) pourrait être remplacé par isset($schema->field[$field])

    C'est assez séduisant (c'est toujours le cas quand on bootstrappe !) mais
    est-utile ?

    Au lieu de créer des types php, il faudra crééer des objets pour chaque
    propriété. Quel impact sur les performances ?

    Si au final on fait ça, il y a un impact un peu partout sur le code actuel.
    Il faut revoir tous les appels aux propriétés/méthodes des schémas et
    disposer de tests unitaires pour vérifier qu'on ne casse rien.
 */

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
                // Si $field est une chaine, on a soit int => name, soit name => type
                if (is_string($field)) {
                    $field = is_int($key) ? ['name' => $field] : ['name' => $key, 'type' => $field];
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
            $fields = [];
            foreach ($result['fields'] as $name => $field) {
                $field = $field->toArray();
                unset($field['name']);
                if (empty($field)) {
                    $fields[] = $name;
                } elseif (count($field) === 1 && isset($field['type'])) {
                    $fields[$name] = $field['type'];
                } else {
                    $fields[$name] = $field;
                }
            }
            $result['fields'] = $fields;
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
            $name = $field->name(); // nouveau nom si renommage
            if (isset($result[$name])) {
                $msg = 'Field %s defined twice';
                throw new InvalidArgumentException(sprintf($msg, $name));
            }

            $result[$name] = $field;
        }
        $this->value['fields'] = $result;
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
        foreach($this->fields() as $name => $field) {
            if (! is_null($field->defaultValue())) {
                $result[$name] = $field->defaultValue(true);
            }
        }

        return $result;
    }
}