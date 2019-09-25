<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Type;

use Docalist\Type\Interfaces\Stringable;
use Docalist\Type\Interfaces\Configurable;
use Docalist\Type\Interfaces\Formattable;
use Docalist\Type\Interfaces\Editable;
use Docalist\Type\Collection;
use Serializable;
use JsonSerializable;
use Docalist\Schema\Schema;
use Docalist\Forms\Container;
use Docalist\Forms\Element;
use Docalist\Forms\Input;
use InvalidArgumentException;

/**
 * Classe de base pour les différents types de données.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Any implements Stringable, Configurable, Formattable, Editable, Serializable, JsonSerializable
{
    /**
     * La valeur php du type.
     *
     * @var mixed
     */
    protected $phpValue;

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
     * @param mixed $value La valeur initiale. Pour les scalaires, vous devez passer un type php natif correspondant
     * au type de l'objet (int, bool, float, ...) Pour les types structurés et les collections, vous devez passer un
     * tableau.
     *
     * @param Schema|null $schema Optionnel, le schéma du type.
     */
    public function __construct($value = null, Schema $schema = null)
    {
        $this->schema = $schema ?: static::getDefaultSchema();
        $this->assign(is_null($value) ? $this->getDefaultValue() : $value);
    }

    /**
     * Charge le schéma par défaut de l'objet.
     *
     * Cette méthode est destinée à être surchargée par les classes descendantes.
     *
     * @return array Un tableau représentant les données du schéma.
     */
    public static function loadSchema(): array
    {
        return [];
    }

    /**
     * Retourne le schéma par défaut de l'objet.
     *
     * La méthode gère un cache des schémas déjà chargés : si le schéma n'est pas encore dans le cache, elle appelle
     * loadSchema() et compile le schéma obtenu.
     *
     * @return Schema
     */
    final public static function getDefaultSchema(): Schema
    {
        $key = get_called_class();

        // Si le schéma est déjà en cache, terminé
        if ($schema = docalist('cache')->get($key)) {
            return $schema;
        }

        // Charge le schéma
        $data = static::loadSchema();
        if (isset($data['type'])) {
            throw new InvalidArgumentException("Property 'type' must not be set in loadSchema");
        }

        // Compile le schéma
        $parent = get_parent_class($key);
        $parent && $data['type'] = $parent;
        $schema = new Schema($data);

        // Stocke le schéma en cache
        docalist('cache')->set($key, $schema);

        // Ok
        return $schema;
    }

    /**
     * Retourne le nom complet de la classe utilisée pour gérer une collection d'objets de ce type.
     *
     * Par défaut la méthode retourne 'Docalist\Type\Collection'. Les classes descendantes peuvent surcharger
     * la méthode pour indiquer une classe plus spécifique, mais la classe retournée doit hériter de la classe
     * Collection de base.
     *
     * @return string Le nom complet de la classe "collection" à utiliser pour ce type.
     */
    public static function getCollectionClass(): string
    {
        return Collection::class;
    }

    // -------------------------------------------------------------------------
    // Valeur par défaut
    // -------------------------------------------------------------------------

    /**
     * Retourne la valeur par défaut du type.
     *
     * La méthode statique getClassDefault() retourne la valeur par défaut des instances de ce type. Les classes
     * descendantes (Boolean, Integer, etc.) surchargent cette méthode et retournent leur propre valeur par défaut.
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
     * La méthode retourne la valeur par défaut indiquée dans le schéma associé à l'objet ou la valeur par défaut
     * du type (getClassDefault) si aucun schéma n'est associé ou s'il n'indique pas de valeur par défaut.
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        $default = $this->schema->getDefaultValue();

        return is_null($default) ? $this->getClassDefault() : $default;
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
     */
    public function assign($value): void
    {
        $this->phpValue = ($value instanceof self) ? $value->getPhpValue() : $value;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    /**
     * Retourne la valeur sous la forme d'un type php natif (string, int, float ou bool pour les types simples,
     * un tableau pour les types structurés et les collections).
     *
     * @return mixed
     */
    public function getPhpValue()
    {
        return $this->phpValue;
    }

    /**
     * Retourne le schéma du type.
     *
     * @return Schema le schéma ou null si le type n'a pas de schéma associé.
     *
     * @deprecated Remplacée par getSchema().
     */
    final public function schema()
    {
        _deprecated_function(__METHOD__, '0.14', 'getSchema');

        return $this->getSchema();
    }

    /**
     * Retourne le schéma du type.
     *
     * @return Schema
     */
    final public function getSchema(): Schema
    {
        return $this->schema;
    }

    // -------------------------------------------------------------------------
    // Interface Stringable
    // -------------------------------------------------------------------------

    final public function __toString(): string
    {
        return json_encode($this->getPhpValue(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // -------------------------------------------------------------------------
    // Interface Serializable
    // -------------------------------------------------------------------------

    /**
     * Retourne une chaine contenant la version sérialisée au format PHP de la valeur du type.
     *
     * @return string
     */
    final public function serialize(): string
    {
        return serialize([$this->phpValue, $this->schema]);
    }

    /**
     * Initialise la valeur du type à partir d'une chaine contenant une valeur sérialisée au format PHP.
     *
     * @param string $serialized
     */
    final public function unserialize($serialized): void
    {
        // php 7.2 ne veut pas qu'on indique le type string du paramètre ("declaration must be compatible with")
        list($this->phpValue, $this->schema) = unserialize($serialized);
    }

    // -------------------------------------------------------------------------
    // Interface JsonSerializable
    // -------------------------------------------------------------------------

    /**
     * Retourne les données à prendre en compte lorsque ce type est sérialisé au format JSON.
     *
     * @return mixed
     */
    final public function jsonSerialize()
    {
        return $this->phpValue;
    }

    // -------------------------------------------------------------------------
    // Interface Filterable
    // -------------------------------------------------------------------------

    /**
     * Filtre les valeurs vides.
     *
     * La méthode filterEmpty() permet de supprimer les valeurs vides d'un type : elle retourne true si la valeur
     * est vide, false sinon.
     *
     * Pour un type scalaire, c'est équivalent à la fonction php empty().
     *
     * Pour un type composite (objet, collection, entité...), la méthode est récursive : elle applique filterEmpty()
     * à chacun des éléments qui composent le type composite, supprime les éléments pour lesquels filterEmpty()
     * a retourné true et retourne true ou false selon que le type composite est vide ou non après traitement.
     *
     * Par défaut ($strict = true), filterEmpty() effectue une comparaison "stricte" pour déterminer si un objet
     * est vide : elle retourne true si toutes les propriétés de l'objet sont vides (autrement dit, un objet qui
     * contient au moins une propriété sera considéré comme non vide).
     *
     * En passant $strict = false, une comparaison spécifique est utilisée pour déterminer si un objet est vide
     * ou non. Pour cela, chaque type objet peut surcharger la méthode filterEmpty() et définir dans quel cas il
     * est vide. Pour un auteur, par exemple, on considérera qu'il est vide si on n'a pas de nom ; pour un résumé,
     * on retournera true si l'objet Content contient un type de contenu mais aucun texte, etc.
     *
     * @param bool $strict Définit le mode utilisé pour déterminer si la valeur est vide ou non (true par défaut).
     *
     * @return bool true si le champ est vide, false sinon.
     */
    public function filterEmpty(bool $strict = true): bool
    {
        return empty($this->phpValue);
    }

    // -------------------------------------------------------------------------
    // Interface Configurable
    // -------------------------------------------------------------------------

    public function getSettingsForm(): Container
    {
        $name = $this->schema->name();
        $form = new Container($name);

        $form->input('label')
            ->setAttribute('id', $name . '-label')
            ->addClass('label regular-text')
            ->setLabel(__('Libellé', 'docalist-core'))
            ->setDescription(__('Libellé utilisé pour désigner ce champ.', 'docalist-core'));

        $form->textarea('description')
            ->setAttribute('id', $name . '-description')
            ->addClass('description large-text autosize')
            ->setAttribute('rows', 1)
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

    public function validateSettings(array $settings): array
    {
        return $settings;
    }

    // -------------------------------------------------------------------------
    // Interface Formattable
    // -------------------------------------------------------------------------

    public function getAvailableFormats(): array
    {
        return [];
    }

    public function getDefaultFormat(): string
    {
        return key($this->getAvailableFormats()) ?: ''; // key() retourne null si tableau vide
    }

    public function getFormatSettingsForm(): Container
    {
        $name = $this->schema->name();
        $form = new Container($name);

        $form->input('label')
            ->setAttribute('id', $name . '-label')
            ->addClass('label regular-text')
            ->setAttribute('placeholder', $this->schema->label())
            ->setLabel(__('Libellé', 'docalist-core'))
            ->setDescription(
                __('Libellé qui sera affiché devant le champ.', 'docalist-core') .
                ' ' .
                __("Par défaut, c'est le libellé indiqué dans la grille de base qui est utilisé.", 'docalist-core')
            );

        $form->input('capability')
            ->setAttribute('id', $name . '-capability')
            ->addClass('capability regular-text')
            ->setAttribute('placeholder', $this->schema->capability())
            ->setLabel(__('Droit requis', 'docalist-core'))
            ->setDescription(
                __('Capacité WordPress requise pour que ce champ soit affiché.', 'docalist-core') .
                ' ' .
                __("Par défaut, c'est la capacité indiquée dans la grille de base qui est utilisée.", 'docalist-core')
            );

        $form->input('before')
            ->setAttribute('id', $name . '-before')
            ->addClass('before regular-text')
            ->setLabel(__('Avant le champ', 'docalist-core'))
            ->setDescription(__('Texte ou code html à insérer avant le contenu du champ.', 'docalist-core'));

        // Propose le choix du format si plusieurs formats sont disponibles
        $formats = $this->getAvailableFormats();
        if (count($formats)) {
            $form->select('format')
                ->setAttribute('id', $name . '-format')
                ->addClass('format regular-text')
                ->setLabel(__("Format d'affichage", 'docalist-core'))
                ->setDescription(__("Choisissez dans la liste le format d'affichage à utiliser.", 'docalist-core'))
                ->setOptions($formats)
                ->setFirstOption(__('(format par défaut)', 'docalist-core'));
        }

        $form->input('after')
            ->setAttribute('id', $name . '-after')
            ->addClass('after regular-text')
            ->setLabel(__('Après le champ', 'docalist-core'))
            ->setDescription(__('Texte ou code html à insérer après le contenu du champ.', 'docalist-core'));

        return $form;
    }

    public function validateFormatSettings(array $settings): array
    {
        return $settings;
    }

    public function getFormattedValue($options = null)
    {
        $format = $this->getOption('format', $options, $this->getDefaultFormat());
        $class = get_class($this);
        $msg = sprintf('getFormattedValue() : invalid format "%s" for class "%s"', $format, $class);

        throw new InvalidArgumentException($msg);
    }

    // -------------------------------------------------------------------------
    // Interface Editable
    // -------------------------------------------------------------------------

    public function getAvailableEditors(): array
    {
        return [];
    }

    public function getDefaultEditor(): string
    {
        return key($this->getAvailableEditors()) ?: ''; // key() retourne null si tableau vide
    }

    public function getEditorSettingsForm(): Container
    {
        $name = $this->schema->name();
        $form = new Container($name);

        $form->input('label')
            ->setAttribute('id', $name . '-label')
            ->addClass('label regular-text')
            ->setAttribute('placeholder', $this->schema->label())
            ->setLabel(__('Libellé en saisie', 'docalist-core'))
            ->setDescription(
                __('Libellé qui sera affiché pour saisir ce champ.', 'docalist-core') .
                ' ' .
                __("Par défaut, c'est le libellé du champ qui est utilisé.", 'docalist-core')
            );

        $form->textarea('description')
            ->setAttribute('id', $name . '-description')
            ->addClass('description large-text autosize')
            ->setAttribute('rows', 1)
            ->setAttribute('placeholder', $this->schema->description())
            ->setLabel(__('Aide à la saisie', 'docalist-core'))
            ->setDescription(
                __("Texte qui sera affiché pour indiquer à l'utilisateur comment saisir le champ.", 'docalist-core') .
                ' ' .
                __("Par défaut, c'est la description du champ qui est utilisée.", 'docalist-core')
            );

        $form->input('capability')
            ->setAttribute('id', $name . '-capability')
            ->addClass('capability regular-text')
            ->setAttribute('placeholder', $this->schema->capability())
            ->setLabel(__('Droit requis', 'docalist-core'))
            ->setDescription(
                __('Capacité WordPress requise pour que ce champ apparaisse dans le formulaire.', 'docalist-core') .
                ' ' .
                __("Par défaut, c'est la capacité du champ qui est utilisée.", 'docalist-core')
            );

        // Propose le choix si plusieurs éditeurs sont disponibles
        $editors = $this->getAvailableEditors();
        if (count($editors) > 1) {
            $default = $this->getSchema()->editor() ?: $this->getDefaultEditor() ?: 'default';
            $default = sprintf(__('Éditeur par défaut indiqué dans le type (%s)', 'docalist-core'), $default);
            $form->select('editor')
                ->setAttribute('id', $name . '-editor')
                ->addClass('editor regular-text')
                ->setLabel(__('Éditeur', 'docalist-core'))
                ->setDescription(__('Choisissez le contrôle utilisé pour saisir ce champ.', 'docalist-core'))
                ->setOptions($editors)
                ->setFirstOption($default);
        }

        $form->select('required')
            ->addClass('required-mode')
            ->setLabel(__('Champ obligatoire', 'docalist-core'))
            ->setDescription(__(
                "Permet de signaler visuellement (pas de contrôle) qu'un champ est requis.
                Plusieurs modes d'affichage sont proposés.",
                'docalist-core'
            ))
            ->setOptions($form->requiredModes())
            ->setFirstOption(__('Non', 'docalist-core'));

        return $form;
    }

    public function validateEditorSettings(array $settings): array
    {
        return $settings;
    }

    /**
     * Retourne les classes CSS qui seront générées par getEditForm() pour l'éditeur passé en paramètre.
     *
     * La méthode retourne une chaine contenant plusieurs classes CSS :
     *
     * - Le nom du champ : une chaine avec le préfixe 'field-' et le nom indiqué dans le schéma du champ
     *   (cette classe n'est pas générée si aucun nom ne figure dans le schéma).
     * - Le type de champ : une chaine avec le préfixe 'type-' construite à partir du nom de la classe PHP
     *   (par exemple 'type-large-text' pour la classe 'Docalist\Type\LargeText').
     * - Le nom de l'éditeur utilisé : si le paramètre $editor est fournit, il est inséré avec le préfixe 'editor'
     *   (par exemple 'editor-textarea' pour le type LargeText).
     * - Les classes css additionnelles fournies dans le paramètre $additional.
     *
     * Exemple : pour un champ 'content' de type LargeText utilisant l'éditeur 'textarea', la méthode retourne les
     * classes CSS "field-content type-large-text editor-textarea autosize".
     *
     * @param string $editor        Optionnel, nom de code de l'éditeur.
     * @param string $additional    Optionnel, classes CSS supplémentaires à ajouter.
     *
     * @return string Une chaine contenant les classes CSS à ajouter à l'éditeur.
     */
    protected function getEditorClass(string $editor = '', string $additional = ''): string
    {
        $css = '';

        // Ajoute une classe contenant le nom du champ
        !empty($name = $this->schema->name()) && $css .= 'field-' . $name;

        // Convertit le nom de classe Php en classe CSS (Docalist\Type\DateTimeInterval -> 'date-time-interval')
        $class = get_class($this);
        $class = substr($class, strrpos($class, '\\') + 1);
        $class = preg_replace_callback('/[A-Z][a-z]+/', function ($match) {
            return '-' . strtolower($match[0]);
        }, $class);
        $class = ltrim($class, '-');
        $css .= ' type-' . $class;

        // Ajoute le nom de l'éditeur
        !empty($editor) && $css .= ' editor-' . $editor;

        // Ajoute les classes css additionnelles
        !empty($additional) && $css.= ' ' . $additional;

        // Ok
        return ltrim($css);
    }

    public function getEditorForm($options = null): Element
    {
        return $this->configureEditorForm(new Input(), $options);
    }

    /**
     * Configure l'éditeur en fonction des options passées en paramètre.
     *
     * La méthode se charge de configurer l'éditeur en fonction des options qui sont gérées par
     * la classe (cf. getEditorSettingsForm).
     *
     * La classe de base (Any) configure :
     *
     * - le nom du champ
     * - le libellé du champ
     * - la description du champ
     * - les classes css de base
     * - l'option "required"
     *
     * Les classes descendantes peuvent surcharger la méthode pour configurer les options qu'elles
     * gèrent, mais elles doivent appeller la méthode héritée.
     *
     * @param Element   $editor     Editeur à configurer.
     * @param array     $options    Options passées à getEditorForm().
     *
     * @return Element
     */
    protected function configureEditorForm(Element $form, $options): Element
    {
        // Nom du champ
        $name = $this->schema->name();
        !empty($name) && $form->setName($name);

        // Libellé
        $label = $this->getOption('label', $options, $name);
        !empty($label) && $form->setLabel($label);

        // Description
        $description = $this->getOption('description', $options, '');
        !empty($description) && $form->setDescription($description);

        // Classes css
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());
        $form->addClass($this->getEditorClass($editor));

        // Champ obligatoire
        $required = $this->getOption('required', $options, '');
        !empty($required) && $form->setRequired($required);

        // Ok
        return $form;
    }

    // -------------------------------------------------------------------------
    // Privé
    // -------------------------------------------------------------------------

    /**
     * Retourne la valeur d'une option.
     *
     * Cette méthode utilitaire permet aux classes descendantes de gérer facilement les options qui sont
     * passées en paramètre à des méthodes comme {@link getEditorForm()} ou {@link getFormattedValue()}.
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
     * La méthode détermine la valeur de l'option indiquée en paramètre en examinant successivement :
     *
     * - les options passées en paramètre,
     * - le schéma du type,
     * - la valeur par défaut passée en paramètre.
     *
     * @param string            $name       Le nom de l'option recherchée.
     * @param Schema|array|null $options    Un tableau ou un schéma contenant les options disponibles.
     * @param mixed             $default    La valeur par défaut à retourner si l'option demandée est introuvable.
     *
     * @return scalar
     */
    final protected function getOption(string $name, $options = null, $default = null)
    {
        // Si des options ont été fournies sous forme d'un schéma et que l'option existe, terminé
        if ($options instanceof Schema && !is_null($value = $options->__call($name))) { /** Schema $options */
            return $value;
        }

        // Si on a un tableau d'options et l'option demandée existe, terminé
        if (is_array($options) && isset($options[$name])) {
            return $options[$name];
        }

        // Si l'option existe dans notre schéma, terminé
        if (!is_null($value = $this->schema->__call($name))) {
            return $value;
        }

        // Option introuvable, retourne la valeur par défaut passée en paramètre
        return $default;
    }
}
