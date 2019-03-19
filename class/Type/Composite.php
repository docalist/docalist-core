<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Type\Any;
use Docalist\Type\Exception\InvalidTypeException;
use Docalist\Forms\Container;
use Docalist\Forms\Table;
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
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Composite extends Any
{
    public static function getClassDefault()
    {
        return [];
    }

    public function assign($value): void
    {
        $array = ($value instanceof Any) ? $value->getPhpValue() : $value;
        if (! is_array($array)) {
            throw new InvalidTypeException('array');
        }

        $this->phpValue = [];
        foreach ($array as $name => $fieldValue) {
            $this->__set($name, $fieldValue);
        }
    }

    public function getPhpValue()
    {
        // Le tableau qui sera retourné
        $result = [];

        // On se base sur le schéma pour retourner les champs toujours dans le même ordre
        foreach ($this->schema->getFieldNames() as $name) {
            // Si le champ n'a pas été créé, continue
            if (!isset($this->phpValue[$name])) {
                continue;
            }

            // Récupère la valeur du champ
            $value = $this->phpValue[$name]->getPhpValue();

            // Si la valeur est vide (cas d'une collection snas éléments, par exemple), continue
            if ($value === []) {
                continue;
            }

            // Ok, stocke la valeur
            $result[$name] = $value;
        }

        // Terminé
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
        // Si le champ a déjà été créé, on change simplement sa valeur
        if (isset($this->phpValue[$name])) {
            $this->phpValue[$name]->assign(is_null($value) ? $this->phpValue[$name]->getDefaultValue() : $value);

            return $this;
        }

        // Vérifie que le champ existe et récupère son schéma
        if (!$this->schema->hasField($name)) {
            throw new InvalidArgumentException('Field "' . $name . '" does not exist');
        }

        // Récupère le schéma du champ
        $field = $this->schema->getField($name);

        // Détermine le type du champ
        $type = $field->collection() ?: $field->type();

        // Si value est déjà du bon type, on le prend tel quel, sinon, on instancie
        $this->phpValue[$name] = ($value instanceof $type) ? $value : new $type($value, $field);

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
        if (!empty($arguments)) {
            return $this->__set($name, $arguments[0]);
        }

        // Appel de la forme : $composite->property()

        // Le champ existe déjà, retourne sa valeur
        if (isset($this->phpValue[$name])) {
            return $this->phpValue[$name]->getPhpValue();
        }

        // Le champ n'existe pas encore, retourne la valeur par défaut
        $field = $this->schema->getField($name);
        $class = $field->collection() ?: $field->type();

        return $class::getClassDefault();
    }

    public function filterEmpty($strict = true)
    {
        foreach ($this->phpValue as $key => $item) { /* @var Any $item */
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
        // TEMP : pour le moment on peut nous passer une grille ou un schéma, à terme, on ne passera que des array
        $options && is_object($options) && $options = $options->value();

        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());
        switch ($editor) {
            case 'container':
                $form = $wrapper = new Container();
                break;

            case 'table':
                $form = $wrapper = new Table();
                break;

            case 'integrated':
                $form = new Container();
                $wrapper = $form->div()->addClass('composite-integrated');
                break;

            default:
                throw new InvalidArgumentException("Invalid Composite editor '$editor'");
        }

        $form
            ->setName($this->schema->name())
            ->addClass($this->getEditorClass($editor))
            ->setLabel($this->getOption('label', $options))
            ->setDescription($this->getOption('description', $options));

        // Récupère la liste des champs à afficher
        $fields = array_keys(isset($options['fields']) ? $options['fields'] : $this->schema->getFields());

        // Génère le formulaire de chaque sous-champ
        foreach ($fields as $name) {
            // Si le champ est marqué "unused" dans le schéma, on l'ignore
            if ($this->schema->getField($name)->unused()) {
                continue;
            }

            // Récupère les options du champ
            $fieldOptions = empty($options['fields'][$name]) ? [] : $options['fields'][$name];

            // Crée l'éditeur de ce champ et l'ajoute au wrapper
            $wrapper->add($this->__get($name)->getEditorForm($fieldOptions));
        }

        return $form;
    }

    /**
     * Formatte le sous-champ dont le nom est passée en paramètre.
     *
     * Cette méthode utilitaire permet aux classes descendantes de formatter les différents sous-champs qu'elles
     * gèrent (cf. TypedText::getFormattedValue par exemple).
     *
     * @param string $name    Nom du champ à formatter.
     * @param array  $options Options d'affichage passées à la méthode getFormattedValue() du Composite.
     *
     * @return string Le champ formatté.
     */
    protected function formatField($name, $options = null)
    {
        // Si le champ est vide, terminé
        if (empty($this->phpValue[$name])) {
            return '';
        }

        // Récupère le champ
        $field = $this->phpValue[$name]; /* @var Any $field */

        // Si aucune option n'a été indiquée pour le champ, utilise le formattage par défaut
        if (empty($options['fields'][$name])) {
            return $field->getFormattedValue();
        }

        // Formatte le champ avec les options indiquées
        return $field->getFormattedValue($options['fields'][$name]);
    }
}
