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

use Docalist\Type\Interfaces\Stringable;
use Docalist\Type\Interfaces\Configurable;
use Docalist\Type\Interfaces\Formattable;
use Docalist\Type\Interfaces\Editable;
use Docalist\Type\Interfaces\Indexable;
use Docalist\MappingBuilder;
use Serializable;
use JsonSerializable;
use Docalist\Schema\Schema;
use Docalist\Forms\Container;
use Docalist\Type\Exception\InvalidTypeException;
use Docalist\Forms\Input;
use InvalidArgumentException;

/**
 * Classe de base pour les différents types de données.
 */
class Any implements Stringable, Configurable, Formattable, Editable, Indexable, Serializable, JsonSerializable
{
    /**
     * La valeur du type.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Le schéma du type.
     *
     * @var Schema
     */
    protected $schema;

    // -------------------------------------------------------------------------
    // Constructeurs
    // -------------------------------------------------------------------------

    /**
     * Crée un nouveau type docalist.
     *
     * @param mixed $value La valeur initiale. Pour les scalaires, vous devez
     * passer un type php natif correspondant au type de l'objet (int, bool,
     * float, ...) Pour les types structurés et les collections, vous devez
     * passer un tableau.
     * @param Schema $schema Optionnel, le schéma du type.
     */
    public function __construct($value = null, Schema $schema = null)
    {
        $this->schema = $schema;
        $this->assign(is_null($value) ? $this->getDefaultValue() : $value);
    }

    /**
     * Crée un type docalist à partir de la valeur php passée en paramètre.
     *
     * La méthode essaie de déterminer le type docalist le plus adapté en
     * fonction du type php de la valeur passée en paramètre :
     *
     * - string -> {@link Text}
     * - int -> {@link Integer}
     * - bool -> {@link Boolean}
     * - float -> {@link Decimal}
     * - array (numeric keys) -> {@link Collection}
     * - array (string keys) -> {@link Composite}
     * - null -> {@link Any}
     *
     * @param mixed $value La valeur Php a convertir en type Docalist.
     * @param Schema $schema Optionnel, le schéma du type.
     *
     * @return Any
     *
     * @throws InvalidTypeException Si le type de la valeur php passée en
     * paramètre n'est pas géré.
     */
    final public static function fromPhpType($value, Schema $schema = null)
    {
        if (is_array($value)) {
            // ça peut être une collection ou un tableau
            // pour tester si les clés sont des int (0..n) on pourrait utiliser
            // array_values($value) === $value
            // cf. https://gist.github.com/Thinkscape/1965669
            // mais dans notre cas, il suffit de tester la clé du 1er élément
            if (is_int(key($value))) { // tableau numérique
                return new Collection($value, $schema);
            }

            return new Composite($value, $schema); // tableau associatif
        }

        if (is_string($value)) {
            return new Text($value, $schema);
        }

        if (is_int($value)) {
            return new Integer($value, $schema);
        }

        if (is_bool($value)) {
            return new Boolean($value, $schema);
        }

        if (is_float($value)) {
            return new Decimal($value, $schema);
        }

        if (is_null($value)) {
            return new self($value, $schema);
        }

        throw new InvalidTypeException('a basic php type');
    }

    // -------------------------------------------------------------------------
    // Valeur par défaut
    // -------------------------------------------------------------------------

    /**
     * Retourne la valeur par défaut du type.
     *
     * La méthode statique getClassDefault() retourne la valeur par défaut des
     * instances de ce type. Les classes descendantes (Boolean, Integer, etc.)
     * surchargent cette méthode et retournent leur propre valeur par défaut.
     *
     * @return mixed
     */
    public static function getClassDefault()
    {
        return;
    }

    /**
     * Retourne la valeur par défaut de l'objet.
     *
     * La méthode retourne la valeur par défaut indiquée dans le schéma associé
     * à l'objet ou la {@link getClassDefault() valeur par défaut du type} si
     * aucun schéma n'est associé ou s'il n'indique pas de valeur par défaut.
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        if ($this->schema) {
            $default = $this->schema->getDefaultValue();
            if (! is_null($default)) {
                return $default;
            }
        }

        return static::getClassDefault();
    }

    // -------------------------------------------------------------------------
    // Initialisation de la valeur
    // -------------------------------------------------------------------------

    /**
     * Assigne une valeur au type.
     *
     * @param mixed $value La valeur à assigner.
     *
     * @return self $this
     *
     * @throws InvalidTypeException Si $value est invalide.
     */
    public function assign($value)
    {
        ($value instanceof self) && $value = $value->value();
        $this->value = $value;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    /**
     * Retourne la valeur sous la forme d'un type php natif (string, int, float
     * ou bool pour les types simples, un tableau pour les types structurés et
     * les collections).
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Retourne le schéma du type.
     *
     * @return Schema le schéma ou null si le type n'a pas de schéma associé.
     */
    final public function schema()
    {
        return $this->schema;
    }

    // -------------------------------------------------------------------------
    // Interface Stringable
    // -------------------------------------------------------------------------
    final public function __toString()
    {
        return json_encode($this->value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // -------------------------------------------------------------------------
    // Interface Serializable
    // -------------------------------------------------------------------------

    /**
     * Retourne une chaine contenant la version sérialisée au format PHP de la
     * valeur du type.
     *
     * @return string
     */
    final public function serialize()
    {
        return serialize($this->value);
    }

    /**
     * Initialise la valeur du type à partir d'une chaine contenant une valeur
     * sérialisée au format PHP.
     *
     * @param string $serialized
     */
    final public function unserialize($serialized)
    {
        $this->value = unserialize($serialized);
    }

    // -------------------------------------------------------------------------
    // Interface JsonSerializable
    // -------------------------------------------------------------------------

    /**
     * Retourne les données à prendre en compte lorsque ce type est sérialisé
     * au format JSON.
     *
     * @return mixed
     */
    final public function jsonSerialize()
    {
        return $this->value;
    }

    // -------------------------------------------------------------------------
    // Interface Filterable
    // -------------------------------------------------------------------------

    /**
     * Filtre les valeurs vides.
     *
     * La méthode filterEmpty() permet de supprimer les valeurs vides d'un
     * type : elle retourne true si la valeur est vide, false sinon.
     *
     * Pour un type scalaire, c'est équivalent à la fonction php empty().
     *
     * Pour un type composite (objet, collection, entité...), la méthode est
     * récursive : elle applique filterEmpty() à chacun des éléments qui
     * composent le type composite, supprime les éléments pour lesquels
     * filterEmpty() a retourné true et retourne true ou false selon que le
     * type composite est vide ou non après traitement.
     *
     * Par défaut ($strict = true), filterEmpty() effectue une comparaison
     * "stricte" pour déterminer si un objet est vide : elle retourne true si
     * toutes les propriétés de l'objet sont vides (autrement dit, un objet qui
     * contient au moins une propriété sera considéré comme non vide).
     *
     * En passant $strict = false, une comparaison spécifique est utilisée pour
     * déterminer si un objet est vide ou non. Pour cela, chaque type objet
     * peut surcharger la méthode filterEmpty() et définir dans quel cas il
     * est vide. Pour un auteur, par exemple, on considérera qu'il est vide si
     * on n'a pas de nom ; pour un résumé, on retournera true si l'objet Content
     * contient un type de contenu mais aucun texte, etc.
     *
     * @param bool $strict Définit le mode de comparaison utilisé pour
     * déterminer si la valeur est vide ou non (true par défaut).
     *
     * @return bool true si le champ est vide, false sinon.
     */
    public function filterEmpty($strict = true)
    {
        return empty($this->value);
    }

    // -------------------------------------------------------------------------
    // Interface Configurable
    // -------------------------------------------------------------------------

    public function getSettingsForm()
    {
        $name = isset($this->schema) ? $this->schema->name() : $this->randomId();

        $form = new Container($name);

        $form->hidden('name')->addClass('name');

        $form->input('label')
            ->setAttribute('id', $name . '-label')
            ->addClass('label regular-text')
            ->setLabel(__('Libellé', 'docalist-core'))
            ->setDescription(__('Libellé utilisé pour désigner ce champ.', 'docalist-core'));

        $form->textarea('description')
            ->setAttribute('id', $name . '-description')
            ->addClass('description large-text')
            ->setAttribute('rows', 2)
            ->setLabel(__('Description', 'docalist-core'))
            ->setDescription(__('Description : rôle, particularités, format...', 'docalist-core'));

        $form->input('capability')
            ->setAttribute('id', $name . '-capability')
            ->addClass('capability regular-text')
            ->setLabel(__('Droit requis', 'docalist-core'))
            ->setDescription(
                __('Capacité WordPress requise pour pouvoir accéder au champ.', 'docalist-core') .
                ' ' .
                __("Si vous n'indiquez rien, aucun droit particulier ne sera nécessaire.", 'docalist-core')
            );

        return $form;
    }

    public function validateSettings(array $settings)
    {
        return $settings;
    }

    // -------------------------------------------------------------------------
    // Interface Formattable
    // -------------------------------------------------------------------------

    public function getAvailableFormats()
    {
        return [];
    }

    public function getDefaultFormat()
    {
        return key($this->getAvailableFormats()); // key() retourne null si tableau vide
    }

    public function getFormatSettingsForm()
    {
        $name = isset($this->schema) ? $this->schema->name() : $this->randomId();

        $form = new Container($name);

        $form->hidden('name')->addClass('name');

        $form->input('labelspec')
            ->setAttribute('id', $name . '-label')
            ->addClass('labelspec regular-text')
            ->setAttribute('placeholder', $this->schema->label() ?: __('(aucun libellé)', 'docalist-core'))
            ->setLabel(__('Libellé', 'docalist-core'))
            ->setDescription(
                __('Libellé qui sera affiché devant le champ.', 'docalist-core') .
                ' ' .
                __("Par défaut, c'est le libellé de la grille de base qui sera utilisé.", 'docalist-core')
            );

        $form->input('capabilityspec')
            ->setAttribute('id', $name . '-capability')
            ->addClass('capabilityspec regular-text')
            ->setAttribute('placeholder', $this->schema->capability())
            ->setLabel(__('Droit requis', 'docalist-core'))
            ->setDescription(
                __('Capacité WordPress requise pour que ce champ soit affiché.', 'docalist-core') .
                ' ' .
                __("Par défaut, c'est la capacité de la grille de base qui sera utilisée.", 'docalist-core')
            );

        $form->input('before')
            ->setAttribute('id', $name . '-before')
            ->addClass('before regular-text')
            ->setLabel(__('Avant le champ', 'docalist-core'))
            ->setDescription(__('Texte ou code html à insérer avant le contenu du champ.', 'docalist-core'));

        $form->input('after')
            ->setAttribute('id', $name . '-after')
            ->addClass('after regular-text')
            ->setLabel(__('Après le champ', 'docalist-core'))
            ->setDescription(__('Texte ou code html à insérer après le contenu du champ.', 'docalist-core'));

        // Propose le choix du format si plusieurs formats sont disponibles
        $formats = $this->getAvailableFormats();
        if (count($formats)) {
            $form->select('format')
                ->setAttribute('id', $name . '-format')
                ->addClass('format regular-text')
                ->setLabel(__("Format d'affichage", 'docalist-core'))
                ->setDescription(__("Choisissez dans la liste le format d'affichage à utiliser.", 'docalist-core'))
                ->setOptions($formats)
                ->setFirstOption(false);
        }

        return $form;
    }

    public function validateFormatSettings(array $settings)
    {
        return $settings;
    }

    public function getFormattedValue($options = null)
    {
        return get_class($this) . '::getFormattedValue() not implemented';
    }

    // -------------------------------------------------------------------------
    // Interface Editable
    // -------------------------------------------------------------------------

    public function getAvailableEditors()
    {
        return [];
    }

    public function getDefaultEditor()
    {
        return key($this->getAvailableEditors()); // key() retourne null si tableau vide
    }

    public function getEditorSettingsForm()
    {
        $name = isset($this->schema) ? $this->schema->name() : $this->randomId();

        $form = new Container($name);

        $form->hidden('name')->addClass('name');

        $form->input('labelspec')
            ->setAttribute('id', $name . '-label')
            ->addClass('labelspec regular-text')
            ->setAttribute('placeholder', $this->schema->label() ?: __('(aucun libellé)', 'docalist-core'))
            ->setLabel(__('Libellé en saisie', 'docalist-core'))
            ->setDescription(
                __('Libellé qui sera affiché pour saisir ce champ.', 'docalist-core') .
                ' ' .
                __("Par défaut, c'est le libellé de la grille de base qui sera utilisé.", 'docalist-core')
            );

        $form->textarea('descriptionspec')
            ->setAttribute('id', $name . '-description')
            ->addClass('description large-text')
            ->setAttribute('rows', 2)
            ->setAttribute('placeholder', $this->schema->description() ?: __('(pas de description)', 'docalist-core'))
            ->setLabel(__('Aide à la saisie', 'docalist-core'))
            ->setDescription(
                __("Texte qui sera affiché pour indiquer à l'utilisateur comment saisir le champ.", 'docalist-core') .
                ' ' .
                __("Par défaut, c'est la description de la grille de base qui sera utilisée.", 'docalist-core')
            );

        $form->input('capabilityspec')
            ->setAttribute('id', $name . '-capability')
            ->addClass('capabilityspec regular-text')
            ->setAttribute('placeholder', $this->schema->capability() ?: '')
            ->setLabel(__('Droit requis', 'docalist-core'))
            ->setDescription(
                __('Capacité WordPress requise pour que ce champ apparaisse dans le formulaire.', 'docalist-core') .
                ' ' .
                __("Par défaut, c'est la capacité de la grille de base qui sera utilisée.", 'docalist-core')
            );

        // Propose le choix du format si plusieurs éditeurs sont disponibles
        $formats = $this->getAvailableEditors();
        if (count($formats) > 1) {
            $form->select('editor')
            ->setAttribute('id', $name . '-editor')
            ->addClass('editor regular-text')
            ->setLabel(__("Editeur", 'docalist-core'))
            ->setDescription(__("Choisissez dans la liste le contrôle à utiliser pour éditeur ce champ.", 'docalist-core'))
            ->setOptions($formats)
            ->setFirstOption(false);
        }


        return $form;
    }

    public function validateEditorSettings(array $settings)
    {
        return $settings;
    }

    public function getEditorForm(array $options = null)
    {
        $name = isset($this->schema) ? $this->schema->name() : $this->randomId();

        return new Input($name);
    }

    // -------------------------------------------------------------------------
    // Interface Indexable
    // -------------------------------------------------------------------------

    public function setupMapping(MappingBuilder $mapping)
    {
        return [];
    }

    public function mapData(array & $document)
    {
        $document[$this->schema->name()][] = $this->value();
    }

    // -------------------------------------------------------------------------
    // Privé
    // -------------------------------------------------------------------------

    /**
     * Génère un nom aléatoire composé de lettres minuscules.
     *
     * @param number $length Longueur du nom à générer. Une longueur de 4 permet
     * de générer environ 30000 id différents.
     *
     * @return string
     */
    protected function randomId($length = 4)
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), - $length);
    }

    /**
     * Retourne la valeur d'une option.
     *
     * La méthode détermine la valeur de l'option indiquée en paramètre en
     * examinant successivement :
     *
     * - les options passées en paramètre,
     * - le schéma du type,
     * - la valeur par défaut passée en paramètre.
     *
     * Cette méthode utilitaire permet aux classes descendantes de gérer
     * facilement les options qui sont passées en paramètre à des méthodes
     * comme {@link getEditorForm()} ou {@link getFormattedValue()}.
     *
     * Exemple :
     *
     * <code>
     * public function getFormattedValue($options = null) {
     *     $sep = $this->getOption('sep', $options,  ', ');
     *     ...
     * }
     * </code>
     *
     * @param string $name Le nom de l'option recherchée.
     * @param Schema|array|null $options Le tableau d'options passées en paramètre.
     * @param mixed $default La valeur par défaut de l'option.
     *
     * @return scalar
     */
    protected function getOption($name, Schema $options = null, $default = null) // TODO : Schema $options ?
    {
        // Les options ont été passées sous forme de Schema, retourne la valeur de l'option si elle existe
        if ($options instanceof Schema) {
            if (isset($options->$name)) {
                return $options->__get($name)->value();
            }
        }

        // Tableau d'options, retourne l'option si elle existe
        elseif (is_array($options)) {
            if (isset($options[$name])) {
                return $options[$name];
            }
        }

        // Erreur
        elseif (!is_null($options)) {
            die('here');
            throw new InvalidArgumentException('Invalid options, expected Schema or array, got ' . gettype($options));
        }

        // L'option demandée ne figure pas dans les options, regarde dans le schéma
        if (isset($this->schema->$name)) {
            return $this->schema->__get($name)->value();
        }

        return $default;
    }
}
