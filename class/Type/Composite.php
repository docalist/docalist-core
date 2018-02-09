<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
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
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Composite extends Any
{
    public static function getClassDefault()
    {
        return [];
    }

    public function assign($value)
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (! is_array($value)) {
            throw new InvalidTypeException('array');
        }

        $this->phpValue = [];
        foreach ($value as $name => $value) {
            $this->__set($name, $value);
        }

        return $this;

        // Voir si on peut évitéer de réinitialiser le tablau à chaque assign ?
        // (faire un array_diff + unset de ce qu'on avait et qu'on n'a plus)
    }

    public function getPhpValue()
    {
        $fields = $this->schema ? $this->schema->getFieldNames() : array_keys($this->phpValue);
        $result = [];
        foreach ($fields as $name) {
            if (isset($this->phpValue[$name])) {
                $value = $this->phpValue[$name]->getPhpValue();
                $value !== [] && $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * Retourne la liste des champs de l'objet.
     *
     * Remarque : contrairement à getPhpValue() qui retourne un tableau de valeurs php,
     * getFields() retourne un tableau de types docalist. Cela permet, par exemple,
     * d'itérer sur tous les champs d'un objet.
     *
     * @return Any[]
     */
    public function getFields()
    {
        return $this->phpValue;
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
        if (isset($this->phpValue[$name])) {
            is_null($value) && $value = $this->phpValue[$name]->getDefaultValue();
            $this->phpValue[$name]->assign($value);

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
            $this->phpValue[$name] = $value;
        }

        // Sinon, on instancie
        else {
            $this->phpValue[$name] = new $type($value, $field);
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
        return isset($this->phpValue[$name]);
    }

    /**
     * Supprime une propriété.
     *
     * @param string $name
     */
    public function __unset($name)
    {
        unset($this->phpValue[$name]);
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
        ! isset($this->phpValue[$name]) && $this->__set($name, null);

        // Retourne l'objet Type
        return $this->phpValue[$name];
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
        if (isset($this->phpValue[$name])) {
            return $this->phpValue[$name]->getPhpValue();
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
        foreach ($this->phpValue as $key => $item) { /** @var Any $item */
            if ($item->filterEmpty($strict)) {
                unset($this->phpValue[$key]);
            }
        }

        return empty($this->phpValue);
    }

    /**
     * Similaire à filterEmpty() mais filtre uniquement la propriété dont le nom
     * est passé en paramètre.
     *
     * @param string $name      Nom de la propriété à filtrer
     * @param bool   $strict    Mode de comparaison.
     */
    protected function filterEmptyProperty($name, $strict = true)
    {
        return ! isset($this->phpValue[$name]) || $this->phpValue[$name]->filterEmpty($strict);
    }

    public function getAvailableEditors()
    {
        return [
            'container' => __('Container', 'docalist-core'),
            'table' => __('Table', 'docalist-core'),
            'integrated' => __('Intégré (tous les champs ensemble)', 'docalist-core'),
        ];
    }

    public function getEditorForm($options = null)
    {
        $editorName = $this->getOption('editor', $options, $this->getDefaultEditor());

        switch ($editorName) {
            case 'container':
                $wrapper = $editor = new Container();
                break;

            case 'table':
                $wrapper = $editor = new Table();
                break;

            case 'integrated':
                $editor = new Container();
                $wrapper = $editor->div()->addClass('composite-integrated');
                break;

            default:
                throw new InvalidArgumentException("Invalid Composite editor '$editorName'");
        }

        $editor
            ->setName($this->schema->name())
            ->setLabel($this->getOption('label', $options))
            ->setDescription($this->getOption('description', $options));

        $class = get_class($this);
        $class = substr($class, strrpos($class, '\\') + 1);
        $class = preg_replace_callback('/[A-Z][a-z]+/', function ($match) {
            return '-' . strtolower($match[0]);
        }, $class);
        $class = ltrim($class, '-');

//        $class .= ' editor-' . $editorName; bof, renvoie l'éditeur du container, pas WPEditor par exemple
        $class .= ' ' . $this->schema->name();
        $editor->addClass($class);

        foreach ($options->getFieldNames() as $name) {
            $fieldOptions = $this->getFieldOptions($name, $options);
            $field = $this->__get($name);
            $unused = $field->getOption('unused', $fieldOptions, false);
            if (!$unused) {
                $wrapper->add($field->getEditorForm($fieldOptions));
            }
        }

        return $editor;
    }

    /**
     * Retourne les options du champ indiqué dans le schéma passé en paramètre.
     *
     * @param string            $name       Le nom du champ recherché.
     * @param Schema|array|null $options    Un tableau ou un schéma contenant les champs disponibles.
     *
     * @return Schema|array|null
     *
     * @throws InvalidArgumentException
     */
    protected function getFieldOptions($name, $options = null)
    {
        // Si des options ont été fournies sous forme d'un schéma et que le champ existe, terminé
        if ($options instanceof Schema && $options->hasField($name)) { /** Schema $options */
            return $options->getField($name);
        }

        // Si on a un tableau d'options et le champ demandé existe, terminé
        if (is_array($options) && isset($options[$name]) && is_array($options[$name])) {
            return $options[$name];
        }

        // Si le champ existe dans notre schéma, terminé
        if ($this->schema->hasField($name)) {
            return $this->schema->getField($name);
        }

        // Champ introuvable, retourne null
        return null;
    }

    protected function formatField($name, $options = null)
    {
        return $this->__get($name)->getFormattedValue($this->getFieldOptions($name, $options));
    }
}
