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

use Docalist\Schema\Schema;
use Docalist\Type\Exception\InvalidTypeException;
use Docalist\Forms\Container;
use InvalidArgumentException;

/**
 * Type Composite.
 *
 * Un Composite est un objet de données qui dispose d'un schéma
 * décrivant les attributs disponibles.
 *
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
class Composite extends Any
{
    /**
     * Cache des schémas.
     *
     * @var Schema[]
     */
    private static $cache = [];
    public static $cacheHit=0, $cacheMiss=0, $wpCacheHit=0, $wpCacheMiss=0;


    public static function getClassDefault()
    {
        return [];
    }

    /**
     * Charge le schéma par défaut de l'objet.
     *
     * Cette méthode est destinée à être surchargée par les classes
     * descendantes.
     *
     * @return array Un tableau représentant les données du schéma.
     */
    protected static function loadSchema()
    {
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
    final public static function defaultSchema()
    {
        $useCache = false;
        $useWPCache = false; // = !WP_DEBUG
        $wpCacheGroup = 'docalist-schemas';

        // Détermine la clé du schéma
        $key = get_called_class();

        // Si on a déjà le schéma en cache, terminé
        if ($useCache && isset(self::$cache[$key])) {
            ++self::$cacheHit;
            return self::$cache[$key];
        }
        ++self::$cacheMiss;

        // Essaie de charger le schéma à partir du cache WordPress
        if ($useWPCache) {
            $schema = wp_cache_get($key, $wpCacheGroup);
            if ($schema !== false) {
                ++self::$wpCacheHit;
                $useCache && self::$cache[$key] = $schema;

                return $schema;
            }
        }
        ++self::$wpCacheMiss;

        // Charge le schéma
        $schema = static::loadSchema();

        // Si loadSchema nous a retourné un tableau, on le compile
        if (is_array($schema)) {
            try {
                $schema = new Schema($schema);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException("$e::loadchema(): " .$e->getMessage());
            }
        }

/*
        $t = array_intersect($schema->getFieldNames(), get_class_methods(__CLASS__));
        if ($t) {
            echo 'WARNING : schema(', get_called_class(), ') conflit nom de champ / nom de méthode : ', implode(', ', $t), '<br />';
        }
*/

        // Stocke le schéma en cache pour la prochaine fois
        $useCache && self::$cache[$key] = $schema;
        if ($useWPCache){
            wp_cache_set($key, $schema, $wpCacheGroup);
        }

        return $schema;
    }

    /**
     * Construit un nouvel objet.
     *
     * @param array $value
     * @param Schema $schema
     */
    public function __construct(array $value = null, Schema $schema = null)
    {
        // Initialise le schéma (utilise le schéma par défaut si besoin)
        parent::__construct($value, $schema ?: $this::defaultSchema());
    }

    public function assign($value)
    {
        ($value instanceof Any) && $value = $value->value();
        if (! is_array($value)) {
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

    public function value()
    {
        $fields = $this->schema ? $this->schema->getFieldNames() : array_keys($this->value);
        $result = [];
        foreach ($fields as $name) {
            if (isset($this->value[$name])) {
                $value = $this->value[$name]->value();
                $value !== [] && $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * Retourne la liste des champs de l'objet.
     *
     * Remarque : contrairement à value() qui retourne un tableau de valeurs,
     * fields() retourne un tableau d'objets Any. Cela permet, par exemple,
     * d'itérer sur tous les champs d'un objet.
     *
     * @return Any[]
     */
    public function getFields()
    {
        return $this->value;
    }

    /**
     * Modifie une propriété de l'objet.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return self $this
     */
    public function __set($name, $value)
    {
        // Si la propriété existe déjà, on change simplement sa valeur
        if (isset($this->value[$name])) {
            is_null($value) && $value = $this->value[$name]->getDefaultValue();
            $this->value[$name]->assign($value);

            return $this;
        }

        // Il faut initialiser le champ

        // Cas d'un objet typé (ayant un schéma)
        if ($this->schema) {
            // Vérifie que le champ existe et récupère son schéma
            if ($this->schema->hasField($name)) {
                $field = $this->schema->getField($name);
            } else {
                $defaultSchema = $this->defaultSchema();
                if ($defaultSchema->hasField($name)) {
                    $field = $defaultSchema->getField($name);
                } else {
                    $msg = 'Field %s does not exist';
                    throw new InvalidArgumentException(sprintf($msg, $name));
                }
            }

            // Crée une collection si le champ est répétable
            if ($collection = $field->collection()) {
                // Si value est déjà une Collection, on prend tel quel
                if ($value instanceof Collection) {
                    $this->value[$name] = $value;
                }

                // Sinon, on instancie
                else {
                    $this->value[$name] = new $collection($value, $field);
                }
            }

            // Crée un type simple sinon
            else {
                $type = $field->type();

                // Si value est déjà du bon type, on le prend tel quel
                if ($value instanceof $type) {
                    $this->value[$name] = $value;
                }                 // Sinon, on instancie
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
                $this->value[$name] = Any::fromPhpType($value);
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
    public function __isset($name)
    {
        return isset($this->value[$name]);
    }

    /**
     * Supprime une propriété.
     *
     * @param string $name
     */
    public function __unset($name)
    {
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
    public function __get($name)
    {
        // Initialise le champ s'il n'existe pas encore
        ! isset($this->value[$name]) && $this->__set($name, null);

        // Retourne l'objet Type
        return $this->value[$name];
    }

    /**
     * Permet d'accéder à une propriété comme s'il s'agissait d'une méthode.
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
     * @param array $arguments Valeur éventuel. Si aucun argument n'est indiqué,
     * la propriété sera accédée via son getter sinon, c'est le setter qui est
     * utilisé.
     *
     * @return Any La méthode retourne soit la propriété demandée (utilisation
     * comme getter), soit l'objet en cours (utilisation comme setter) pour
     * permettre le chainage de méthodes.
     */
    public function __call($name, $arguments)
    {
        // $composite->property($x) permet de modifier la valeur d'un champ
        if ($arguments) {
            return $this->__set($name, $arguments[0]);
        }

        // Appel de la forme : $composite->property()

        // Le champ existe déjà, retourne sa valeur
        if (isset($this->value[$name])) {
            return $this->value[$name]->value();
        }

        // Le champ n'existe pas encore, retourne la valeur par défaut
        if ($this->schema) {
            $field = $this->schema->getField($name);
            if ($collection = $field->collection()) {
                return $collection::getClassDefault();
            }

            $type = $field->type();

            return $type::getClassDefault();
        }

        return Any::getClassDefault();
    }

    public function filterEmpty($strict = true)
    {
        foreach ($this->value as $key => $item) { /* @var Any $item */
            if ($item->filterEmpty($strict)) {
                unset($this->value[$key]);
            }
        }

        return empty($this->value);
    }

    /**
     * Similaire à filterEmpty() mais filtre uniquement la propriété dont le nom
     * est passé en paramètre.
     *
     * @param string $name Nom de la propriété à filtrer
     * @param string $strict Mode de comparaison.
     */
    protected function filterEmptyProperty($name, $strict = true)
    {
        return ! isset($this->value[$name]) || $this->value[$name]->filterEmpty($strict);
    }

    public function getEditorForm(array $options = null)
    {
        $name = isset($this->schema) ? $this->schema->name() : $this->randomId();

        $editor = new Container($name);

        foreach ($this->schema->getFieldNames() as $name) {
            $editor->add($this->__get($name)->getEditorForm());
        }

        return $editor;
    }

    /**
     * Retourne les options du champ indiqué dans le schéma passé en paramètre.
     *
     * @param string $name
     * @param Schema $options
     *
     * @return Schema|array|null
     *
     * @throws InvalidArgumentException
     */
    protected function getFieldOptions($name, Schema $options = null) // TODO : Schema $options ?
    {
        // Les options ont été passées sous forme de Schema, retourne le schéma du champ
        if ($options instanceof Schema) {
            if ($options->hasField($name)) {
                return $options->getField($name);
            }
        }

        // Tableau d'options, teste si on a quelque chose pour le champ demandé
        elseif (is_array($options)) {
            if (isset($options['fields'][$name])) {
                return $options['fields'][$name];
            }
        }

        // Erreur
        elseif( !is_null($options)) {
            throw new InvalidArgumentException('Invalid options, expected Schema or array, got ' . gettype($options));
        }

        // Le champ demandé ne figure pas dans les options, regarde dans le schéma
        if ($this->schema->hasField($name)) {
            return $this->schema->getField($name);
        }

        // Champ trouvé nulle part
        return null;
    }

    protected function formatField($name, $options = null)
    {
        return $this->__get($name)->getFormattedValue($this->getFieldOptions($name, $options));
    }
}
