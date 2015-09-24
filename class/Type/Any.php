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

use Serializable;
use JsonSerializable;
use Docalist\Schema\Schema;
use Docalist\Forms\Fragment;
use Docalist\Type\Exception\InvalidTypeException;
use Docalist\Forms\Tag;
use Docalist\Forms\Select;

/**
 * Classe de base pour les différents types de données.
 */
class Any implements Stringable, Formattable, Editable, Serializable, JsonSerializable
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
            return new Any($value, $schema);
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
        return null;
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
            $default = $this->schema->defaultValue();
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
        ($value instanceof Any) && $value = $value->value();
        $this->value = $value;

        return $this;
    }

    /**
     * Réinitialise le type à sa valeur par défaut.
     *
     * @return self $this
     */
    final public function reset()
    {
        return $this->assign($this->getDefaultValue());
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
    // Tests et comparaisons
    // -------------------------------------------------------------------------


    /**
     * Teste si deux types sont identiques.
     *
     * Par défaut, les types sont identiques si ils ont la même classe et
     * la même valeur.
     *
     * @param Any $other
     *
     * @return boolean
     */
    public function equals(Any $other)
    {
        return get_class($this) === get_class($other) && $this->value() === $other->value();
    }

    // -------------------------------------------------------------------------
    // Interface Stringable
    // -------------------------------------------------------------------------
    public function __toString()
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
        $this->assign(unserialize($serialized));
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
        $name = isset($this->schema->name) ? $this->schema->name() : $this->randomId();

        $form = new Fragment($name);

        $form->hidden('name')->attribute('class', 'name');

        $form->input('label')
            ->attribute('id', $name . '-label')
            ->attribute('class', 'label regular-text')
            ->label(__('Libellé du champ', 'docalist-core'))
            ->description(__('Libellé utilisé pour désigner ce champ.', 'docalist-core'));

        $form->textarea('description')
            ->attribute('id', $name . '-description')
            ->attribute('class', 'description large-text')
            ->attribute('rows', 2)
            ->label(__('Description', 'docalist-core'))
            ->description(__('Description du champ : rôle, particularités, format...', 'docalist-core'));

        $form->input('capability')
            ->attribute('id', $name . '-capability')
            ->attribute('class', 'capability regular-text')
            ->label(__('Droit requis', 'docalist-core'))
            ->description(__("Capacité WordPress requise pour accéder à ce champ ou vide si aucun droit particulier n'est requis.", 'docalist-core'));

        return $form;
    }

    public function validateSettings(array $settings)
    {
        return $settings;
    }

    // -------------------------------------------------------------------------
    // Interface Formattable
    // -------------------------------------------------------------------------
    public function getFormattedValue(array $options = null)
    {
        return get_class($this) . '::getFormattedValue() not implemented';
    }

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
        $name = isset($this->schema->name) ? $this->schema->name() : $this->randomId();

        $form = new Fragment($name);

        $form->hidden('name')->attribute('class', 'name');

        $form->input('labelspec')
            ->attribute('id', $name . '-label')
            ->attribute('class', 'labelspec regular-text')
            ->attribute('placeholder', $this->schema->label ?  : __('(aucun libellé)', 'docalist-core'))
            ->label(__('Libellé', 'docalist-core'))
            ->description(__("Libellé affiché avant le champ. Par défaut, c'est le même que dans la grille de saisie mais vous pouvez saisir un nouveau texte si vous voulez un libellé différent.", 'docalist-core'));

        $form->input('capabilityspec')
            ->attribute('id', $name . '-label')
            ->attribute('class', 'capabilityspec regular-text')
            ->attribute('placeholder', $this->schema->capability ?  : '')
            ->label(__('Droit requis', 'docalist-core'))
            ->description(__("Droit requis pour afficher ce champ. Par défaut, c'est le droit du champ qui figure dans la grille de base qui est utilisé.", 'docalist-core'));

        $form->input('before')
            ->attribute('id', $name . '-before')
            ->attribute('class', 'before regular-text')
            ->label(__('Avant le champ', 'docalist-core'))
            ->description(__('Texte ou code html à insérer avant le contenu du champ.', 'docalist-core'));

        $form->input('after')
            ->attribute('id', $name . '-before')
            ->attribute('class', 'after regular-text')
            ->label(__('Après le champ', 'docalist-core'))
            ->description(__('Texte ou code html à insérer après le contenu du champ.', 'docalist-core'));

        // Propose le choix du format si plusieurs formats sont disponibles
        $formats = $this->getAvailableFormats();
        if (count($formats)) {
            $form->select('format')
                ->attribute('id', $name . '-format')
                ->attribute('class', 'format regular-text')
                ->label(__("Format d'affichage", 'docalist-core'))
                ->description(__("Choisissez dans la liste le format d'affichage à utiliser.", 'docalist-core'))
                ->options($formats)
                ->firstOption(false);
        }

        return $form;
    }

    public function validateFormatSettings(array $settings)
    {
        return $settings;
    }

    // -------------------------------------------------------------------------
    // Interface Editable
    // -------------------------------------------------------------------------
    public function getEditorForm(array $options = null)
    {
        return new Tag('p', get_class($this) . '::getEditorForm() not implemented');
    }

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
        $name = isset($this->schema->name) ? $this->schema->name() : $this->randomId();

        $form = new Fragment($name);

        $form->hidden('name')
            ->attribute('class', 'name');

        $form->input('labelspec')
            ->attribute('id', $name . '-label')
            ->attribute('class', 'labelspec regular-text')
            ->attribute('placeholder', $this->schema->label ?  : __('(aucun libellé)', 'docalist-core'))
            ->label(__('Libellé en saisie', 'docalist-core'))
            ->description(__("Libellé affiché en saisie. Par défaut, c'est le libellé indiqué dans les paramètres de base qui est utilisé mais vous pouvez indiquer un libellé différent si vous le souhaitez.", 'docalist-core'));

        $form->textarea('descriptionspec')
            ->attribute('id', $name . '-description')
            ->attribute('class', 'description large-text')
            ->attribute('rows', 2)
            ->attribute('placeholder', $this->schema->description ?  : __('(pas de description)', 'docalist-core'))
            ->label(__('Aide à la saisie', 'docalist-core'))
            ->description(__("Texte qui sera affiché pour indiquer à l'utilisateur comment saisir le champ. Par défaut, c'est la description du champ qui figure dans la grille de base qui est utilisée.", 'docalist-core'));

        $form->input('capabilityspec')
            ->attribute('id', $name . '-label')
            ->attribute('class', 'capabilityspec regular-text')
            ->attribute('placeholder', $this->schema->capability ?  : '')
            ->label(__('Droit requis', 'docalist-core'))
            ->description(__("Droit requis pour que ce champ apparaissent dans le formulaire. Par défaut, c'est le droit du champ qui figure dans la grille de base qui est utilisé.", 'docalist-core'));

        $default = $this->editForm()
            ->name('default')
            ->label(__('Valeur par défaut', 'docalist-core'));

        if ($this->schema->repeatable() && ! (($default instanceof Select) && $default->multiple())) {
            // Ajoute le bouton "add" pour les champs répétables
            // Sauf s'il s'agit d'un Select multiple
            $default->repeatable(true);
        }

        $form->add($default);

        return $form;
    }

    public function validateEditorSettings(array $settings)
    {
        return $settings;
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
    private function randomId($length = 4)
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
     * public function getFormattedValue(array $options = null) {
     *     $sep = $this->getOption('sep', $options,  ', ');
     *     ...
     * }
     * </code>
     *
     * @param string $name Le nom de l'option recherchée.
     * @param array|null $options Le tableau d'options passées en paramètre.
     * @param mixed $default La valeur par défaut de l'option.
     */
    protected function getOption($name, array $options = null, $default = null) {
        if (isset($options[$name])) {
            return $options[$name];
        }

        if (isset($this->schema->value[$name])) {
            return $this->schema->value[$name];
        }

        return $default;
    }
}
