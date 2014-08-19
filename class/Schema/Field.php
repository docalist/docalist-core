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
namespace Docalist\Schema;

use InvalidArgumentException;

/**
 * Décrit les propriétés d'un champ au sein d'un schéma.
 */
class Field extends Schema {
    /**
     * Nom du champ.
     *
     * @var string
     */
    protected $name = '';

    /**
     * Type du champ.
     *
     * La propriété contient toujours un nom de Type complet (avec namespace).
     * Les alias (string, int, float, bool...) sont convertis lors de la
     * création du champ.
     *
     * @var string
     */
    protected $type = 'Docalist\Type\String';

    /**
     * Indique si le champ est répétable.
     *
     * @var boolean
     */
    protected $repeatable = false;

    /**
     * Valeur par défaut du champ.
     *
     * @var mixed
     */
    protected $default = null;

    /**
     * Libellé du champ.
     *
     * @var string
     */
    protected $label = '';

    /**
     * Description du champ.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Nom du sous-champ utilisé comme clé si le champ est une collection
     * d'entités,
     *
     * @var string
     */
    protected $key = null;

    public function __construct(array $data, $defaultNamespace = '') {
        static $alias = [ // garder synchro avec le tableau de type()
            'any'     => 'Docalist\Type\Any',
            'scalar'  => 'Docalist\Type\Scalar',
            'string'  => 'Docalist\Type\String',
            'int'     => 'Docalist\Type\Integer',
            'bool'    => 'Docalist\Type\Boolean',
            'float'   => 'Docalist\Type\Float',

            // alias secondaires
            'integer' => 'Docalist\Type\Integer',
            'boolean' => 'Docalist\Type\Boolean',
            'double'  => 'Docalist\Type\Float',
            'real'    => 'Docalist\Type\Float',
            'text'    => 'Docalist\Type\String',
            'long'    => 'Docalist\Type\Integer',
        ];

        // Teste si le champ contient des propriétés qu'on ne connait pas
        if ($unknown = array_diff_key($data, get_object_vars($this))) {
            $msg = 'Unknown field property(es) in field "%s": "%s"';
            $name = isset($data['name']) ? $data['name'] : '';
            throw new InvalidArgumentException(sprintf($msg, $name, implode(', ', array_keys($unknown))));
        }

        // Nom du champ
        if (isset($data['name'])) {
            $this->name = (string) $data['name'];
        }

        // Type
        if (isset($data['type'])) {
            $this->type = (string) $data['type'];

            // Répétable : type='xx*' équivaut à type='xx' + repeatable=true
            if (substr($this->type, -1) === '*') {
                $this->type = substr($this->type, 0, -1);
                $data['repeatable'] = true;
            }

            // Si le type indiqué est un alias, on le traduit en nom de Type
            if (isset($alias[$this->type])) {
                $this->type = $alias[$this->type];
            }

            // Le type indiqué doit être un nom de Type docalist
            else {
                // On peut avoir soit un nom relatif au namespace en cours,
                // soit un nom de classe complet. Si la classe existe à la fois
                // dans le namespace par défaut et dans le namespace global,
                // c'est celle du namespace en cours qui est prise en compte.
                if ($defaultNamespace) {
                    $class = $defaultNamespace . '\\' . $this->type;
                    if (class_exists($class, true)) {
                        $this->type = $class;
                    }
                }

                // La classe indiquée doit être un nom de Type et doit exister
                if (! is_a($this->type, 'Docalist\Type\Any', true)) {
                    $msg = 'Invalid type "%s" for field "%s"';

                    throw new InvalidArgumentException(sprintf($msg, $this->type, $this->name));
                }
            }
        }

        // Repeatable
        if (isset($data['repeatable'])) {
            $this->repeatable = (bool) $data['repeatable'];
        }

        // Default
        if (isset($data['default'])) {
            $this->default = $data['default'];
        } elseif ($this->repeatable) {
            $this->default = [];
        }

        // Key
        if (isset($data['key'])) {
            $this->key = (string) $data['key'];
        }

        // Label
        if (isset($data['label'])) {
            $this->label = (string) $data['label'];
        }

        // Description
        if (isset($data['description'])) {
            $this->description = (string) $data['description'];
        }

        // Fields
        if (isset($data['fields'])) {
            parent::__construct($data['fields']);
        } else {
            $this->fields = false; // cf. fields()
        /*
            $type = $this->type;

            if (is_a($type, 'Docalist\Type\Object', true)) {
                $this->fields = $type::defaultSchema()->fields;
            }*/
        }
    }

    public function fields() {
        // On initialise fields tardivement (plutôt que dans le constructeur)
        // Pour pouvoir gérer les schémas récursifs (par exemple un objet Money
        // qui contient des conversions Money*). Si on initialise dès le
        // constructeur, on obtient une boucle infinie sur defaultSchema.
        // Du coup on le fait içi, c'est-à-dire une fois que le format a été
        // chargé et compilé.
        if ($this->fields === false) {
            $type = $this->type;
            if (is_a($type, 'Docalist\Type\Object', true)) {
                $this->fields = $type::defaultSchema()->fields;
            } else {
                $this->fields = null;
            }
        }

        return $this->fields;
    }

    /**
     * Retourne le nom du champ.
     *
     * @return string
     */
    public function name() {
        return $this->name;
    }

    /**
     * Retourne le type du champ en convertissant les noms de types connus en
     * alias.
     *
     * Pour les types standards, la méthode retourne un alias (par exemple
     * 'int' au lieu de 'Docalist\Type\Integer'). Pour les autres types,
     * la méthode retourne le nom de classe complet, comme className().
     *
     * @return string
     */
    public function type() {
        static $alias = [ // garder synchro avec le tableau de __construct()
            'Docalist\Type\Any'     => 'any',
            'Docalist\Type\Scalar'  => 'scalar',
            'Docalist\Type\String'  => 'string',
            'Docalist\Type\Integer' => 'int',
            'Docalist\Type\Boolean' => 'bool',
            'Docalist\Type\Float'   => 'float',
        ];

        return isset($alias[$this->type]) ? $alias[$this->type] : $this->type;
    }

    /**
     * Retourne le type exact du champ.
     *
     * Cette méthode est similaire à type() mais elle retourne toujours le nom
     * exact de la classe de Type utilisée pour représenter le champ (i.e. elle
     * ne retourne jamais un alias comme 'int ou 'string').
     *
     * @return string
     */
    public function className() {
        return $this->type;
    }

    /**
     * Indique si le champ est répétable.
     *
     * @return bool
     */
    public function repeatable() {
        return $this->repeatable;
    }

    /**
     * Pour une collection d'entités, indique le sous-champ utilisé comme clé
     * pour les entrées de la collection.
     *
     * @return string|null
     */
    public function key() {
        return $this->key;
    }

    /**
     * Retourne la valeur par défaut du champ.
     *
     * @return mixed
     */
    public function defaultValue($execCallable = false) {
        if (! $execCallable || ! is_callable($this->default)) {
            return $this->default;
        }

        return call_user_func($this->default, $this);
    }

    /**
     * Retourne le libellé du champ, ou son nom si le champ n'a pas de libellé.
     *
     * @return string
     */
    public function label() {
        return $this->label ?: $this->name;
    }

    /**
     * Retourne la description du champ.
     *
     * @return string
     */
    public function description() {
        return $this->description;
    }

    /**
     * Convertit le schéma en tableau.
     *
     * @return array
     */
    public function toArray() {
        $field = ['name' => $this->name];
        $type = $this->type();
        /* $type !== 'string' && */$field['type'] = $type;
        $this->repeatable && $field['repeatable'] = $this->repeatable;
        $this->default && $field['default'] = $this->default;
        $this->label && $field['label'] = $this->label;
        $this->description && $field['description'] = $this->description;
        $this->key && $field['key'] = $this->key;
        $this->fields && $field['fields'] = parent::toArray();

        return $field;
    }
}