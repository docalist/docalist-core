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

use Docalist\Type\Composite;
use Docalist\Type\Collection;
use InvalidArgumentException;

/**
 * Un schéma est un {@link Docalist\Type\Composite objet composite} qui permet de
 * décrire les attributs d'un {@link Docalist\Type\Any type de données Docalist}.
 *
 * Sur le principe, c'est juste un moyen simple de stocker une liste de propriétés
 * de la forme clé => valeur.
 *
 * La plupart des propriétés sont libres (il faut juste que ce soit des scalaires)
 * mais certaines propriétés connues sont contrôlées.
 *
 * Un schéma peut également avoir une propriété 'fields' qui décrit les propriétés
 * des sous-champs d'un type composite. Dans ce cas, chaque élément de la collection
 * fields sera elle-même un schéma (i.e. la structure obtenue est récursive).
 *
 * Les schémas sont notamment utilisés pour définir la liste des champs des composites
 * et pour définir les grilles (affichage, saisie, etc.) des entités.
 */
class Schema extends Composite
{
    // Alias courts pour les types de base docalist (deprecated)
    private static $alias = [
        'any' => 'Docalist\Type\Any',

        'bool' => 'Docalist\Type\Boolean',
        'boolean' => 'Docalist\Type\Boolean',

        'float' => 'Docalist\Type\Decimal',
        'double' => 'Docalist\Type\Decimal',
        'real' => 'Docalist\Type\Decimal',

        'int' => 'Docalist\Type\Integer',
        'integer' => 'Docalist\Type\Integer',
        'long' => 'Docalist\Type\Integer',

        'scalar' => 'Docalist\Type\Scalar',

        'string' => 'Docalist\Type\Text',
        'text' => 'Docalist\Type\Text',
    ];

    /**
     * Construit un nouveau schéma.
     *
     * @param array $value Propriétés du schéma.
     *
     * @throws InvalidArgumentException Si le schéma contient des erreurs.
     */
    public function __construct(array $value)
    {
        // Valide le type
        if (isset($value['type'])) {
            $type = (string) $value['type'];

            // Répétable : type='xx*' équivaut à type='xx' + repeatable=true
            if (substr($type, -1) === '*') {
                $type = substr($type, 0, -1);
                $value['repeatable'] = true;
            }

            // Si le type indiqué est un alias, on le traduit en nom de Type
            if (isset(self::$alias[$type])) {
                $type = self::$alias[$type];
            }

            // Le type indiqué doit être un nom de Type docalist
            else {
                if (is_a($type, 'Docalist\Type\Collection', true)) {
                    $value['collection'] = $type;
                    $type = $type::type();
                }

                // La classe indiquée doit être un nom de Type et doit exister
                if (! is_a($type, 'Docalist\Type\Any', true)) {
                    $msg = 'Invalid type "%s" for field "%s"';

                    throw new InvalidArgumentException(sprintf($msg, $type, isset($value['name']) ? $value['name'] : '(noname)'));
                }
            }
            $value['type'] = $type;
        }

        // Valide repeatable et collection
        if (isset($value['repeatable']) && $value['repeatable'] && !isset($value['collection'])) {
            $value['collection'] = 'Docalist\Type\Collection';
        }
        unset($value['repeatable']);

        $this->schema = null;
        $this->assign($value);
    }

    public function __set($name, $value)
    {
        // Si la propriété existe déjà, on change simplement sa valeur
        if (isset($this->value[$name])) {
            $this->value[$name]->assign($value);

            return $this;
        }

        // Propriétés génériques
        if ($name !== 'fields') {
            $this->value[$name] = self::fromPhpType($value);

            return $this;
        }

        // Propriété 'fields'
        if (! is_array($value)) {
            throw new InvalidArgumentException("Invalid value for property 'fields', expected array");
        }

        $fields = [];
        foreach ($value as $key => $field) {
            // Si $field est une chaine, on a soit int => name, soit name => type
            if (is_string($field)) {
                $field = is_int($key) ? ['name' => $field] : ['name' => $key, 'type' => $field];
            }

            // Champ de la forme : nom => array(propriétés)
            elseif (is_string($key)) {
                $field['name'] = $key;
            }

            // Crée le schéma du champ
            $field = new self($field);

            // Vérifie que le nom du champ est unique
            $name = $field->name();
            if (isset($fields[$name])) {
                throw new InvalidArgumentException("Field $name defined twice");
            }

            // Stocke le champ
            $fields[$name] = $field;
        }
        $collection = new Collection();
        $collection->value = $fields;
        $this->value['fields'] = $collection;

        return $this;
    }

    /**
     * Retourne la liste des champs.
     *
     * @return Schema[]
     */
    public function getFields()
    {
        /*
            On initialise fields tardivement (plutôt que dans le constructeur)
            pour pouvoir gérer les schémas récursifs (par exemple un objet Money
            qui contient des conversions Money*). Si on initialise dès le
            constructeur, on obtient une boucle infinie sur defaultSchema.
            Du coup on le fait içi, c'est-à-dire une fois que le format a été
            chargé et compilé.
        */

        // Si 'fields' est déjà initialisé, terminé
        if (array_key_exists('fields', $this->value)) {
            return is_null($this->value['fields']) ? [] : $this->value['fields']->value;
        }

        // Si on n'a pas de type ou si n'est pas un composite, impossible d'aller consulter un schéma
        $type = isset($this->value['type']) ? $this->value['type']->value() : '';
        if (! is_a($type, 'Docalist\Type\Composite', true)) { // inutile de tester empty($type), is_a le fait
            $this->value['fields'] = null;

            return [];
        }

        // Ok, Récupère la liste des champs du composite
        $this->value['fields'] = $type::defaultSchema()->value['fields'];

        return $this->value['fields']->value;

        /* remarque: c'est la même collection qui est partagée entre plusieurs schémas. Problème ? */
    }

    /**
     * Retourne le nom des champs.
     *
     * @return string[]
     */
    public function getFieldNames()
    {
        return array_keys($this->getFields());
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
    public function getField($field)
    {
        $fields = $this->getFields();
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
    public function hasField($field)
    {
        $fields = $this->getFields();

        return isset($fields[$field]);
    }

    /**
     * Retourne la valeur par défaut du schéma.
     *
     * S'il s'agit d'un schéma simple (sans fields), la méthode retourne le contenu
     * de la propriété 'default' (ou null si elle n'existe pas).
     *
     * Si la propriété 'fields' existe, la méthode retourne un tableau contenant la
     * valeur par défaut de chacun des champs.
     *
     * @return array|scalar|null
     */
    public function getDefaultValue()
    {
        if (isset($this->value['fields'])) {
            $result = [];
            foreach ($this->value['fields'] as $name => $field) {
                if ($name && ! is_null($default = $field->getDefaultValue())) {
                    $result[$name] = $default;
                }
            }

            return $result;
        }

        return isset($this->value['default']) ? $this->value['default'] : null;
    }
}
