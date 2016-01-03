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
use Docalist\Forms\Table;

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
    public static function getClassDefault()
    {
        return [];
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

        // Vérifie que le champ existe et récupère son schéma
        if ($this->schema->hasField($name)) {
            $field = $this->schema->getField($name);
        } else {
            $schema = static::getDefaultSchema();
            if ($schema->hasField($name)) {
                $field = $schema->getField($name);
            } else {
                $msg = 'Field %s does not exist';
                throw new InvalidArgumentException(sprintf($msg, $name));
            }
        }

        // Détermine le type du champ
        $type = $field->collection() ?: $field->type();

        // Si value est déjà du bon type, on le prend tel quel
        if ($value instanceof $type) {
            $this->value[$name] = $value;
        }

        // Sinon, on instancie
        else {
            $this->value[$name] = new $type($value, $field);
        }

        // Ok
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

    public function getAvailableEditors()
    {
        return [
            'container' => __('Container', 'docalist-core'),
            'table' => __('Table', 'docalist-core'),
        ];
    }

    public function getEditorForm($options = null)
    {
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());

        switch ($editor) {
            case 'container':
                $editor = new Container();
                break;

            case 'table':
                $editor = new Table();
                break;

            default:
                throw new InvalidArgumentException("Invalid Composite editor '$editor'");
        }

        $editor
            ->setName($this->schema->name())
            ->setLabel($this->getOption('label', $options))
            ->setDescription($this->getOption('description', $options));

        foreach ($options->getFieldNames() as $name) { // TODO : si $options n'est pas une grille
            $fieldOptions = $this->getFieldOptions($name, $options);
            $field = $this->__get($name);
            $unused = $field->getOption('unused', $fieldOptions, false);
            if (!$unused) {
                $editor->add($field->getEditorForm($fieldOptions));
            }
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
        elseif (!is_null($options)) {
            throw new InvalidArgumentException('Invalid options, expected Schema or array, got ' . gettype($options));
        }

        // Le champ demandé ne figure pas dans les options, regarde dans le schéma
        if ($this->schema->hasField($name)) {
            return $this->schema->getField($name);
        }

        // Champ trouvé nulle part
        return;
    }

    protected function formatField($name, $options = null)
    {
        return $this->__get($name)->getFormattedValue($this->getFieldOptions($name, $options));
    }
}
