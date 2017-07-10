<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Core
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Schema;

use Docalist\Type\Composite;
use Docalist\Type\Collection;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Un schéma permet de décrire les attributs d'un {@link Docalist\Type\Any type de données Docalist}.
 *
 * Sur le principe, c'est juste un moyen simple de stocker une liste de propriétés de la forme clé => valeur.
 *
 * La plupart des propriétés sont libres (il faut juste que ce soit des scalaires) mais certaines propriétés connues
 * sont contrôlées.
 *
 * Dans le cas d'un type composite, un schéma peut également avoir une propriété 'fields' qui décrit les propriétés
 * des sous-champs. Dans ce cas, chaque élément de la collection fields sera elle-même un schéma.
 *
 * Les schémas sont notamment utilisés pour définir la liste des champs des entités docalist et pour gérer les
 * différentes grilles (affichage, saisie, etc.).
 */
class Schema implements JsonSerializable
{
    /**
     * Liste des propriétés du schéma.
     *
     * @var array
     */
    protected $properties;

    /**
     * Construit un nouveau schéma.
     *
     * @param array|null $properties Propriétés du schéma.
     *
     * @throws InvalidArgumentException Si le schéma contient des erreurs.
     */
    public function __construct(array $properties = null)
    {
        // Cas particulier : schéma vide
        if (empty($properties)) {
            $this->properties = [];

            return;
        }

        // Valide et normalise les propriétés du schéma
        $this->validate($properties);

        // Gère l'héritage si la propriété 'type' est définie
        if (isset($properties['type']) && is_a($properties['type'], 'Docalist\Type\Any', true)) {
            $parent = $properties['type']::getDefaultSchema();
            $properties = $this->mergeProperties($parent->value(), $properties);
        }

        // Compile la liste des champs
        if (isset($properties['fields'])) {
            foreach ($properties['fields'] as & $field) {
                $field = new self($field);
            }
        }

        // Trie les propriétés
        $this->properties = $this->sortProperties($properties);
    }

    /**
     * Valide et normalise les propriétés passées en paramètre.
     *
     * @param array $properties
     *
     * @return self
     */
    protected function validate(array & $properties)
    {
        return $this->validateType($properties)
                    ->validateCollection($properties)
                    ->validateFields($properties);
    }

    /**
     * Valide la propriété 'type'.
     *
     * @param array $properties
     *
     * @return self
     */
    protected function validateType(array & $properties)
    {
        if (!isset($properties['type'])) {
            return $this;
        }

        $type = $properties['type'];
        if (! is_string($type)) {
            throw new InvalidArgumentException("Invalid 'type': expected string, got " . gettype($type));
        }

        // Une étoile à la fin indique un typerépétable. Par défaut, c'est le type qui indique la collection.
        if (substr($type, -1) === '*') {
            $type = $properties['type'] = substr($type, 0, -1);
            if (is_a($type, 'Docalist\Type\Any', true)) {
                $properties['collection'] = $type::getCollectionClass();
            } else {
                // on peut arriver là si c'est un Grid (qui n'hérite pas de Any).
                $properties['collection'] = 'Docalist\Type\Collection';
            }
        }

        // Si le type indiqué est une collection, c'est la collection qui fournit le type des éléments
        if (is_a($type, 'Docalist\Type\Collection', true)) {
            if (isset($properties['collection'])) {
                throw new InvalidArgumentException('Collection defined twice (in type and in collection)');
            }
            $properties['collection'] = $type; /** @var Collection $type */
            $type = $properties['type'] = $type::getType();
        }

        // Le type doit désigner un type docalist (ou un schéma)
        if (! is_a($type, 'Docalist\Type\Any', true) && ! is_a($type, self::class, true)) {
            throw new InvalidArgumentException("Invalid type '$type'");
        }

        return $this;
    }

    /**
     * Valide la propriété 'collection'.
     *
     * @param array $properties
     *
     * @return self
     */
    protected function validateCollection(array & $properties)
    {
        if (!isset($properties['collection'])) {
            return $this;
        }

        $collection = $properties['collection']; /** @var Collection $collection */
        if (! is_string($collection)) {
            throw new InvalidArgumentException("Invalid 'collection': expected string, got " . gettype($collection));
        }

        // La collection indiquée doit être une classe descendante de Collection
        if (!is_a($collection, 'Docalist\Type\Collection', true)) {
            throw new InvalidArgumentException("$collection is not a Collection");
        }

        // Si on a un type, il doit être compatible avec le type indiqué par la collection
        $type = $collection::getType();
        if (isset($properties['type'])) {
            if (!is_a($properties['type'], $type, true) && !is_a($properties['type'], self::class, true)) {
                throw new InvalidArgumentException(
                    "Type '{$properties['type']}' is not compatible with collection type '$type'"
                );
            }
        }

        // Sinon, c'est la collection qui indique le type des items
        else {
            $properties['type'] = $collection::getType();
        }

        return $this;
    }

    /**
     * Valide la liste de champs.
     *
     * @param array $properties
     *
     * @return self
     */
    protected function validateFields(array & $properties)
    {
        if (!isset($properties['fields'])) {
            return $this;
        }

        if (isset($properties['type']) && ! is_a($properties['type'], 'Docalist\Type\Composite', true)) {
            throw new InvalidArgumentException('Scalar type can not have fields');
        }

        if (!is_array($properties['fields'])) {
            throw new InvalidArgumentException("Property 'fields' must be an array");
        }

        $fields = [];
        foreach ($properties['fields'] as $key => $field) {
            // Si $field est une chaine, on a soit int => name, soit name => type
            if (is_string($field)) {
                $field = is_int($key) ? ['name' => $field] : ['name' => $key, 'type' => $field];
            }

            // Champ de la forme : nom => array(propriétés)
            elseif (is_string($key)) {
                if (!is_array($field)) {
                    throw new InvalidArgumentException("Invalid properties for field '$key', expected array");
                }

                if (isset($field['name']) && $field['name'] !== $key) {
                    throw new InvalidArgumentException("Field name defined twice");
                }
                $field['name'] = $key;
            }

            // Valide les propriétés du champ
            $this->validate($field);

            // Vérifie que le champ a un nom
            if (!isset($field['name'])) {
                throw new InvalidArgumentException('Field without name');
            }

            // Vérifie que le nom du champ est unique
            $name = $field['name'];
            if (isset($fields[$name])) {
                throw new InvalidArgumentException("Field $name defined twice");
            }

            // Stocke le champ
            $fields[$name] = $field;
        }

        $properties['fields'] = $fields;

        return $this;
    }

    /**
     * Fusionne les propriétés passées en paramètre ($data) avec les propriétés existantes ($properties).
     *
     * @param array $properties Propriétés existantes.
     * @param array $data       Nouveaux paramètres.
     *
     * @return array Propriétés mises à jour.
     *
     * @throws InvalidArgumentException
     */
    protected function mergeProperties(array $properties, array $data)
    {
        // Supprime la liste des champs pour ne conserver que les propriétés simples
        $fields = [];
        if (isset($data['fields'])) {
            $fields = $data['fields'];
            unset($data['fields']);
        }

        // Met à jour les propriétés
        foreach ($data as $name => $value) {
            $value = $this->filterProperty($value);
            if (is_null($value)) {
                unset($properties[$name]);
            } else {
                $properties[$name] = $value;
            }
        }

        // Met à jour la liste des champs
        if ($fields) {
            $result = [];
            foreach ($fields as $name => $data) {
                // Changement des paramétres d'un champ qui existait déjà
                if (isset($properties['fields'][$name])) {
                    $data = $this->mergeProperties($properties['fields'][$name], $data);
                }

                // Vérifie que le nom du champ est unique
                // remarque : ne peut arriver que lors de la sauvegarde d'une grille
                // pour un schéma, validate() garantit déjà que les noms sont uniques
                // $name = nouveau nom si renommage autorisé dans le formulaire, ancien sinon
                $name = isset($data['name']) ? $data['name'] : $name;
                if (isset($result[$name])) {
                    throw new InvalidArgumentException("Field '$name' defined twice");
                }

                // Stocke le champ
                $result[$name] = $data;
            }

            $properties['fields'] = isset($properties['fields']) ? ($result + $properties['fields']) : $result;
        }

        // Ok
        return $properties;
    }

    /**
     * Filtre la propriété passée en paramètre si elle est vide.
     *
     * Une propriété est vide si sa valeur est null, une chaine vide ou un tableau vide.
     *
     * Si la propriété est un tableau, chacun des éléments du tableau est filtré récursivement et la propriété sera
     * supprimée si le tableau obtenu est vide.
     *
     * @param mixed $property La valeur à filtrer.
     *
     * @return mixed|null
     */
    protected function filterProperty($property)
    {
        is_array($property) && $property = array_filter($property, [$this, 'filterProperty']);
        if (is_null($property) || $property === '' || $property === []) {
            return null;
        }

        return $property;
    }

    /**
     * Trie les propriétés du schéma dans un ordre prévisible.
     *
     * Du fait de l'héritage, les propriétés se retrouvent dans un ordre qui n'est pas très logique (les propriétés
     * héritées se retrouvent après les propriétés locales).
     *
     * Cette méthode y remédie en triant les propriétés qu'on connaît pour que l'ordre soit à peu près toujours
     * le même.
     *
     * Cela simplifie notamment les comparaisons de grilles (pour voir ce qui a été changé).
     *
     * @param array $properties
     *
     * @return array
     */
    protected function sortProperties(array $properties)
    {
        $order = [
            'name',
            'unused',
            'gridtype',
            'type','collection',
            'state',
            'label', 'description',
            'reltype', 'relfilter','table',
            'default',
            'explode',
            'editor',
            'before', 'format', 'after',
        ];

        // Propriétés qu'on connaît
        $result = [];
        foreach ($order as $name) {
            if (isset($properties[$name])) {
                $result[$name] = $properties[$name];
                unset($properties[$name]);
            }
        }

        // Propriétés qu'on veut en dernier
        $last = [];
        foreach (['fields'] as $name) {
            if (isset($properties[$name])) {
                $last[$name] = $properties[$name];
                unset($properties[$name]);
            }
        }

        // Les propriétés qu'on ne connaît pas (celles qui restent) vont entre les deux
        return $result + $properties + $last;
    }

    /**
     * Retourne la liste des propriétés du schéma.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Indique si le schéma a des champs.
     *
     * @return bool
     */
    public function hasFields()
    {
        return isset($this->properties['fields']);
    }

    /**
     * Retourne la liste des champs.
     *
     * @return Schema[]
     */
    public function getFields()
    {
        return isset($this->properties['fields']) ? $this->properties['fields'] : [];
    }

    /**
     * Retourne le nom des champs.
     *
     * @return string[]
     */
    public function getFieldNames()
    {
        return isset($this->properties['fields']) ? array_keys($this->properties['fields']) : [];
    }

    /**
     * Retourne le schéma du champ indiqué.
     *
     * @param string $name Le nom du champ.
     *
     * @return Schema
     *
     * @throws InvalidArgumentException si le champ indiqué n'existe pas.
     */
    public function getField($name)
    {
        if (isset($this->properties['fields'][$name])) {
            return $this->properties['fields'][$name];
        }

        throw new InvalidArgumentException("Field '$name' does not exist");
    }

    /**
     * Indique si le schéma contient le champ indiqué.
     *
     * @param string $name Le nom du champ à tester.
     *
     * @return bool
     */
    public function hasField($name)
    {
        return isset($this->properties['fields'][$name]);
    }

    /**
     * Retourne la valeur par défaut du schéma.
     *
     * La méthode retourne :
     *
     * 1. le contenu de la propriété 'default' si celle-ci est définie dans le schéma ;
     * 2. sinon, un tableau vide s'il s'agit d'une collection ;
     * 3. sinon, un tableau contenant la valeur par défaut des différents champs si la propriété 'fields' existe ;
     * 4. sinon, null.
     *
     * @return array|scalar|null
     */
    public function getDefaultValue()
    {
        // Si le champ a une valeur par défaut, terminé
        if (isset($this->properties['default'])) {
            return $this->properties['default'];
        }

        // Si le champ est une collection, retourne un tableau vide
        if (isset($this->properties['collection'])) {
            return [];
        }

        // Si le champ est un composite, retourne un tableau contenant les valeurs par défaut des champs
        $result = null;
        if (isset($this->properties['fields'])) {
            foreach ($this->properties['fields'] as $name => $field) {
                $default = $field->getDefaultValue();
                !is_null($default) && $result[$name] = $default;
            }
        }

        return $result;
    }

    /**
     * Permet d'accéder aux propriétés du schéma comme s'il sagissait de méthodes.
     *
     * @param string    $name       Nom de la propriété.
     * @param array     $arguments  Paramètres éventuels.
     *
     * @throws InvalidArgumentException
     */
    public function __call($name, array $arguments = [])
    {
        if (!empty($arguments)) {
            throw new InvalidArgumentException('Schema::_call() called with arguments');
        }

        return isset($this->properties[$name]) ? $this->properties[$name] : null;
    }

    /**
     * Convertit le schéma en tableau php.
     *
     * @return array
     */
    public function value()
    {
        $value = $this->properties;
        if (isset($value['fields'])) {
            foreach ($value['fields'] as &$field) {
                $field = $field->value();
            }
        }

        return $value;
    }

    /**
     * Hack : comme un schéma (une grille) se comporte "comme" un type docalist (mais sans en être un)
     * on est obligé d'avoir une méthode getPhpValue() sinon les schémas ne sont pas récupérés quand on
     * enregistre un type (repository appelle Schema->getPhpValue(), qui appelle Schema->call('getPhpValue')
     * qui retourne vide) et du coup on perd toutes les grilles.
     *
     * @return array
     */
    public function getPhpValue()
    {
        return $this->value();
    }

    // -------------------------------------------------------------------------
    // Interface JsonSerializable
    // -------------------------------------------------------------------------

    /**
     * Retourne les données à prendre en compte lorsque ce type est sérialisé au format JSON.
     *
     * @return array
     */
    final public function jsonSerialize()
    {
        // utilisé uniquement par biblio/exporter paramètres
        return $this->properties;
    }
}
