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
namespace Docalist\Type;

use Docalist\Schema\Schema;
use Docalist\Type\Exception\InvalidTypeException;

/**
 * Type objet.
 *
 * Un objet de données a un schéma qui décrit les attributs disponibles.
 * Les classes descendantes doivent implémenter la méthode statique
 * loadSchema() qui retourne le schéma par défaut de l'objet.
 *
 * Les attributs de l'objet peuvent être manipulés comme des propriétés :
 *
 * <code>
 *     $book->title = 'titre';
 *     echo $book->title;
 * </code>
 *
 * ou comme des fonctions :
 *
 * <code>
 *     $book->title('titre');
 *     echo $book->title();
 * </code>
 *
 * Lors de sa création, un objet utilisera soit le schéma par défaut de la
 * classe, soit une version personnalisée du schéma transmise en paramètre au
 * constructeur.
 */
class Object extends Any {
    static protected $default = [];

    /**
     * Charge le schéma par défaut de l'objet.
     *
     * Cette méthode est destinée à être surchargée par les classes
     * descendantes.
     *
     * @return array Un tableau représentant les données du schéma.
     */
    protected static function loadSchema() {
        return null;
    }

    /**
     * Retourne le schéma par défaut de l'objet.
     *
     * La méthode gère un cache des schémas déjà chargés. Si le schéma n'est pas
     * encore dans le cache, elle appelle loadSchema().
     *
     * @return Schema
     */
    public static final function defaultSchema() {
        // Charge le format par défaut
        $schema = static::loadSchema();
        // retourne un schéma, un tableau ou null

        // Si loadSchema nous a retourné un tableau, on le compile
        if (is_array($schema)) {
            // Détermine le namespace pour résoudre les noms de classe relatifs
            $class = get_called_class();
            $namespace = substr($class, 0, strrpos($class, '\\'));
            $schema = new Schema($schema, $namespace);
        }

        return $schema;

        // TODO: cache
    }

    /**
     * Construit un nouvel objet.
     *
     * @param array $value
     * @param Schema $schema
     */
    public function __construct(array $value = null, Schema $schema = null) {
        // Initialise le schéma (utilise le schéma par défaut si besoin)
        parent::__construct($value, $schema ?: $this::defaultSchema());
    }

    public function assign($value) {
        ($value instanceof Any) && $value = $value->value();
        if (! is_array($value)){
            throw new InvalidTypeException('array');
        }

        $this->value = [];
        foreach ($value as $name => $value) {
            $this->__set($name, $value);
        }

        return $this;

        // TODO ne pas réinitialiser le tablau à chaque assign ?
        // (faire un array_diff + unset de ce qu'on avait et qu'on n'a plus)
    }

    public function value() {
        // important : on retourne les champs dans l'ordre du schéma
        $fields = $this->schema ? $this->schema->fieldNames() : array_keys($this->value);
        $result = [];
        foreach($fields as $name) {
            if (isset($this->value[$name])) {
                $value = $this->value[$name]->value();
                $value !== [] && $result[$name] = $value;
            }
        }
        return $result;
    }

    /**
     * Modifie une propriété de l'objet.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return self $this
     */
    public function __set($name, $value) {
        // Si la propriété existe déjà, on change simplement sa valeur
        if (isset($this->value[$name])) {
            is_null($value) && $value = $this->value[$name]->defaultValue();
            $this->value[$name]->assign($value);
            return $this;
            // TODO : si $value est déjà un type du bon type
        }

        // Il faut initialiser le champ

        // Cas d'un objet typé (ayant un schéma)
        if ($this->schema) {
            // Vérifie que le champ existe et récupère son schéma
            $field = $this->schema->field($name);

            // Crée une collection si le champ est répétable
            if ($field->repeatable()) {
                // Si value est déjà une Collection, on prend tel quel
                if ($value instanceof Collection) {
                    $this->value[$name] = $value;
                }
                // Sinon, on instancie
                else {
                    $this->value[$name] = new Collection($value, $field);
                }
            }

            // Crée un type simple sinon
            else {
                $type = $field->className();

                // Si value est déjà du bon type, on le prend tel quel
                if ($value instanceof $type) {
                    $this->value[$name] = $value;
                }
                // Sinon, on instancie
                else {
                    $this->value[$name] = new $type($value, $field);
                }
            }
        }

        // Cas d'un objet libre (sans schéma associé)
        else {
            // Si value est déjà un Type, rien à faire
            if ($value instanceof Any) {
                $this->value[$name] = $value;
            }

            // Sinon, essaie de créer le Type le plus adapté
            else {
                $this->value[$name] = self::guessType($value);
            }
        }

        return $this;
    }

    /**
     * Indique si une propriété existe.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name) {
        return isset($this->value[$name]);
    }

    /**
     * Supprime une propriété.
     *
     * @param string $name
     */
    public function __unset($name) {
        unset($this->value[$name]);
    }

    /**
     * Retourne une propriété de l'objet.
     *
     * @param string $name
     *
     * @return Any
     *
     * @throws InvalidArgumentException Si la propriété n'existe pas dans le schéma.
     */
    public function __get($name) {
        // Initialise le champ s'il n'existe pas encore
        ! isset($this->value[$name]) && $this->__set($name, null);

        // Retourne l'objet Type
        return $this->value[$name];
    }

    /**
     * Permet d'accéder à une propriété comme s'il s'gissait d'une méthode.
     *
     * Si un objet livre a une propriété "title', vous pouvez y accéder (getter)
     * en appellant :
     * <code>
     *    echo $book->title();
     * </code>
     *
     * et vous pouvez la modifier (setter) en appellant :
     * <code>
     *    $book->title('nouveau titre);
     * </code>
     *
     * Lorsqu'elle est utilisée comme setter, le chainage de méthodes est
     * autorisé. Par exemple :
     *
     * <code>
     *    $book->title('titre)->author('aut')->tags(['roman', 'histoire'];
     * </code>
     *
     * @param string $name Nom de la propriété.
     * @param array $arguments Valeur éventuel. Si aucun argument 'est indiqué,
     * la propriété sera accédée via son getter sinon, c'est le setter qui est
     * utilisé.
     *
     * @return Any La méthode retourne soit la propriété demandée (utilisation
     * comme getter), soit l'objet en cours (utilisation comme setter) pour
     * permettre le chainage de méthodes.
     */
    public function __call($name, $arguments) {
        // $object->property($x) permet de modifier la valeur d'un champ
        if ($arguments) {
            return $this->__set($name, $arguments[0]);
        }

        // Appel de la forme : $object->property($x)

        // Le champ existe déjà, retourne sa valeur
        if (isset($this->value[$name])) {
            return $this->value[$name]->value();
        }

        // Le champ n'existe pas encore, retourne la valeur par défaut
        if ($this->schema) {
            $field = $this->schema->field($name);
            if ($field->repeatable()) {
                return Collection::classDefault();
            }

            $type = $field->className();
            return $type::classDefault();
        }

        return Any::classDefault();
    }

    public function __toString() {
        if (empty($this->value)) {
            return '{ }';
        }

        $result = '{';
        self::$indent .= '    ';
        $fields = $this->schema ? $this->schema->fieldNames() : array_keys($this->value);
        foreach($fields as $name) {
            if (isset($this->value[$name])) {
                $result .= PHP_EOL . self::$indent . $name . ': ' . $this->value[$name]->__toString();
            }
        }
        self::$indent = substr(self::$indent, 0, -4);
        $result .= PHP_EOL . self::$indent . '}';

        return $result;
    }
}