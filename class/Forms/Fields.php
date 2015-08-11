<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Forms
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */

namespace Docalist\Forms;

use ReflectionClass;
use Docalist\Type\Object;

/**
 * Représente une liste de champs.
 *
 * Un objet Fields est un champ qui peut lui-même contenir d'autres champs.
 */
abstract class Fields extends Field {
    /**
     * @var array(Field) Les champs de la liste.
     */
    protected $fields = array();

    /**
     * Retourne les champs que contient cette liste de champs.
     *
     * @return Field[] Un tableau de {@link Field champs}.
     */
    public function fields() {
        return $this->fields;
    }

    /**
     * Ajoute un champ à la liste.
     *
     * @param string|Field $field Le champ à ajouter. Vous pouvez soit passer
     * en paramètre un champ déjà construit (par exemple new Input()) ou bien
     * une chaine indiquant le type du champ à créer (par exemple 'input').
     *
     * Dans ce dernier cas, les paramètres passés à la méthode doivent
     * correspondre aux paramètres attendus par le constructeur du champ qui
     * sera créé.
     *
     * @return Field Le champ créé.
     */
    public function add($field) {
        if (is_string($field)) {
            $class = new ReflectionClass(__NAMESPACE__ .'\\' . $field);
            $args = func_get_args();
            array_shift($args);
            $field = $class->newInstanceArgs($args);
        }

        $field->parent = $this;
        $this->fields[] = $field;

        return $field;
    }

    /**
     * @inheritdoc
     */
    public function data($data = null) {
        if (is_null($data)) {
            $data = array();
            foreach ($this->fields as $field) {
                if ($field->name) {
                    $data[$field->name] = $field->data;
                }
                // BUG : si le champ n'a pas de nom et qu'il a des enfants, on ne récupère pas leur valeur (exemple : facet widget)
            }

            return $data;
        }

        $this->bind($data);
        // ? boucler sur les champs erreurs si data contient autre chose que des
        // champs

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function isArray() {
        return true;
    }

    public final function bind($data) {
        $debug = false;

        if($debug) echo '&rArr;Fields ', $this->type(), '.', ($this->name() ?: $this->label()), '::bind()<br />';

        if (is_null($this->schema) && $data instanceof Object) {
            if($debug) echo "data est un Object<br />";
            $this->schema = $data->schema();
            if($debug) echo "this.schema initialisé<br />";
        }

        if ($this->name) {
            if (is_object($data)) {
                $data = isset($data->{$this->name}) ? $data->{$this->name} : null;
            } else {
                $data = isset($data[$this->name]) ? $data[$this->name] : null;
            }
        }

        if ($debug) {
            echo "store ";
            if (is_null($data)) echo "null";
            elseif(is_array($data)) echo empty($data) ? "empty array" : "array";
            elseif(is_object($data)) echo "object of type ", get_class($data);
            elseif (is_scalar($data)) echo gettype($data), ' ', var_export($data, true);
            echo "<br />";
        }
        $this->data = $data;

        if ($debug) echo 'Binding des sous-champs<blockquote>';
        foreach ($this->fields as $field) {

            if ($this->schema) {
                if ($name = $field->name()) {
                    if ($this->schema->has($name)) {
                        $schema = $this->schema->field($name);
                        if($debug) echo "Le schema a un champ qui s'appelle ", $name, "<br />";
                        if($debug) echo "Schéma du champ <b>$name</b> : <pre>", json_encode($schema->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), '</pre>';
                        $field->schema($schema);
                    } else if($debug) echo "Le champ $name n'existe pas dans le schéma<br />";
                }
            }
            $field->bind($this->data);
        }
        if ($debug) echo '</blockquote>';

        return $this;
    }

    protected function bindOccurence($data) {
        foreach ($this->fields as $field) {
            $field->bind($data);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function prepare($theme = 'default') {
        foreach ($this->fields as $field) {
            $field->prepare($theme);
        }

        return $this;
    }

    /**
     * Crée un champ de type "Input type=text" et l'ajoute
     * à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return Input Le champ créé.
     */
    public function input($name) {
        return $this->add(new Input($name));
    }

    /**
     * Crée un champ de type "Input type=password" et l'ajoute
     * à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return Password Le champ créé.
     */
    public function password($name) {
        return $this->add(new Password($name));
    }

    /**
     * Crée un champ de type "Input type=hidden" et l'ajoute
     * à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return Hidden Le champ créé.
     */
    public function hidden($name) {
        return $this->add(new Hidden($name));
    }

    /**
     * Crée un champ de type "textarea" et l'ajoute à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return Textarea Le champ créé.
     */
    public function textarea($name) {
        return $this->add(new Textarea($name));
    }

    /**
     * Crée un champ de type "Input type=checkbox" et l'ajoute
     * à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return Checkbox Le champ créé.
     */
    public function checkbox($name) {
        return $this->add(new Checkbox($name));
    }

    /**
     * Crée un champ de type "Input type=radio" et l'ajoute
     * à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return Radio Le champ créé.
     */
    public function radio($name) {
        return $this->add(new Radio($name));
    }

    /**
     * Crée un champ de type "button" et l'ajoute
     * à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return Button Le champ créé.
     */
    public function button($label = null) {
        return $this->add(new Button($label));
    }

    /**
     * Crée un champ de type "Input type=submit" et l'ajoute
     * à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return Submit Le champ créé.
     */
    public function submit($label = null) {
        return $this->add(new Submit($label));
    }

    /**
     * Crée un champ de type "Input type=reset" et l'ajoute
     * à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return Reset Le champ créé.
     */
    public function reset($label = null) {
        return $this->add(new Reset($label));
    }

    /**
     * Crée un champ de type "Select" et l'ajoute à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return Select Le champ créé.
     */
    public function select($name) {
        return $this->add(new Select($name));
    }

    /**
     * Crée un champ de type "Checklist" et l'ajoute à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return Select Le champ créé.
     */
    public function checklist($name) {
        return $this->add(new Checklist($name));
    }

    /**
     * Crée un champ de type "Fieldset" et l'ajoute à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return Fieldset Le champ créé.
     */
    public function fieldset($legend) {
        return $this->add(new Fieldset($legend));
    }

    /**
     * Crée un élément de type "Tag" et l'ajoute à la collection.
     *
     * @param string $tag Le tag de l'élément (p, div, etc.)
     * @param string $content Le contenu du tag.
     *
     * @return Fieldset Le champ créé.
     */
    public function tag($tag, $content = null) {
        return $this->add(new Tag($tag, $content));
    }

    /**
     * Crée un champ de type "Table" et l'ajoute à la collection.
     *
     * @param string $tag Le nom du champ.
     *
     * @return Table Le champ créé.
     */
    public function table($name) {
        return $this->add(new Table($name));
    }

    /**
     * Crée un champ de type "Div" et l'ajoute à la collection.
     *
     * @param string $tag Le nom du champ.
     *
     * @return Table Le champ créé.
     */
    public function div($name = null) {
        return $this->add(new Div($name));
    }

    public function toArray($withData = false) {
        $t = parent::toArray($withData);

        if ($this->fields) {
            $t['fields'] = array();
            foreach ($this->fields as $field) {
                $t['fields'][] = $field->toArray($withData);
            }
            if (isset($t['data'])) {
                $data = $t['data'];
                unset($t['data']);
                $t['data'] = $data;
            }

        }

        return $t;
    }

    /**
     * Crée un champ de type "Selectize" et l'ajoute à la collection.
     *
     * @param string $name Le nom du champ.
     *
     * @return TableLookup Le champ créé.
     */
    public function tableLookup($name, $table = null, $valueField = 'code', $labelField = 'label') {
        return $this->add(new TableLookup($name, $table, $valueField, $labelField));
    }
}