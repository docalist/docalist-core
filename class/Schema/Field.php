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
namespace Docalist\Schema;

use InvalidArgumentException;

/**
 * Décrit les propriétés d'un champ au sein d'un schéma.
 *
 * @amethod string name() Retourne le nom du champ.
 *
 * @amethod string type() Retourne le type du champ.
 * La propriété contient toujours un nom de Type complet incluant le namespace
 * ('Docalist\Type\Text' par défaut). Les alias (string, int, float, bool...)
 * sont convertis lors de la création du champ.
 *
 * @amethod boolean repeatable() Indique si le champ est répétable.
 *
 * @amethod string label() Retourne le libellé du champ.
 *
 * @amethod string description() Retourne la description du champ.
 *
 * @amethod string key() Si le champ est une collection d'objets, retourne le
 * nom du sous-champ utilisé comme clé pour la collection.
 *
 */
class Field extends Schema {
    public function __construct(array $value, $defaultNamespace = '') {
        static $alias = [ // garder synchro avec le tableau de friendlyType()
            'any'     => 'Docalist\Type\Any',
            'scalar'  => 'Docalist\Type\Scalar',
            'string'  => 'Docalist\Type\Text',
            'int'     => 'Docalist\Type\Integer',
            'bool'    => 'Docalist\Type\Boolean',
            'float'   => 'Docalist\Type\Float',

            // alias secondaires
            'integer' => 'Docalist\Type\Integer',
            'boolean' => 'Docalist\Type\Boolean',
            'double'  => 'Docalist\Type\Float',
            'real'    => 'Docalist\Type\Float',
            'text'    => 'Docalist\Type\Text',
            'long'    => 'Docalist\Type\Integer',
        ];

        // Type
        if (isset($value['type'])) {
            $type = (string) $value['type'];

            // Répétable : type='xx*' équivaut à type='xx' + repeatable=true
            if (substr($type, -1) === '*') {
                $type = substr($type, 0, -1);
                $value['repeatable'] = true;
            }

            // Si le type indiqué est un alias, on le traduit en nom de Type
            if (isset($alias[$type])) {
                $type = $alias[$type];
            }

            // Le type indiqué doit être un nom de Type docalist
            else {
                // On peut avoir soit un nom relatif au namespace en cours,
                // (juste un nom de classe sans namespace et sans antislashs),
                // soit un nom de classe complet incluant le namespace.
                // Si c'est un nom de classe court et que la classe existe à la
                // fois dans le namespace en cours et dans le namespace global,
                // c'est celle du namespace en cours qui est prise en compte.
                if ($defaultNamespace && false === strpos($type, '\\')) {
                    $class = $defaultNamespace . '\\' . $type;
                    class_exists($class, true) && $type = $class;
                }

                if (is_a($type, 'Docalist\Type\Collection', true)) {
                    // $value['repeatable'] = true;
                    $value['collection'] = $type;
                    $ns = $type::ns();
                    $type = $type::type();
                    if ($ns) {
                        $class = $ns . '\\' . $type;
                        class_exists($class, true) && $type = $class;
                    }
                }

                // La classe indiquée doit être un nom de Type et doit exister
                if (! is_a($type, 'Docalist\Type\Any', true)) {
                    $msg = 'Invalid type "%s" for field "%s"';

                    throw new InvalidArgumentException(sprintf($msg, $type, $this->name()));
                }
            }
            $value['type'] = $type;
        }
        if (isset($value['repeatable']) && $value['repeatable'] && !isset($value['collection'])) {
            $value['collection'] = 'Docalist\Type\Collection';
            unset($value['repeatable']);
        }

        // Fields et autres propriétés
        parent::__construct($value);
    }

    public function name() {
        return isset($this->value['name']) ? $this->value['name'] : '';
    }

    public function type() {
        return isset($this->value['type']) ? $this->value['type'] : 'Docalist\Type\Text';
    }

    public function collection() {
        return isset($this->value['collection']) ? $this->value['collection'] : null;
    }

    public function repeatable() {
        return isset($this->value['collection']);
    }

    public function label() {
        if (isset($this->value['label'])) {
            return $this->value['label'];
        }

        if (isset($this->value['name'])) {
            return $this->value['name'];
        }

        return '';
    }

    public function description() {
        return isset($this->value['description']) ? $this->value['description'] : '';
    }

    public function key() {
        return isset($this->value['key']) ? $this->value['key'] : null;
    }


    public function fields() {
        // On initialise fields tardivement (plutôt que dans le constructeur)
        // pour pouvoir gérer les schémas récursifs (par exemple un objet Money
        // qui contient des conversions Money*). Si on initialise dès le
        // constructeur, on obtient une boucle infinie sur defaultSchema.
        // Du coup on le fait içi, c'est-à-dire une fois que le format a été
        // chargé et compilé.
        if (! array_key_exists('fields', $this->value)) {
            $type = $this->type();
            if (is_a($type, 'Docalist\Type\Composite', true)) {
                $this->value['fields'] = $type::defaultSchema()->fields();
            } else {
                $this->value['fields']= null;
            }
        }

        return parent::fields();
    }

    /**
     * Retourne le type du champ en convertissant les noms de types connus en
     * alias.
     *
     * Pour les types standards, la méthode retourne un alias (par exemple
     * 'int' au lieu de 'Docalist\Type\Integer'). Pour les autres types,
     * la méthode retourne le nom de classe complet, comme type().
     *
     * @return string
     */
    public function friendlyType() {
        static $alias = [ // garder synchro avec le tableau de __construct()
            'Docalist\Type\Any'     => 'any',
            'Docalist\Type\Scalar'  => 'scalar',
            'Docalist\Type\Text'    => 'string',
            'Docalist\Type\Integer' => 'int',
            'Docalist\Type\Boolean' => 'bool',
            'Docalist\Type\Float'   => 'float',
        ];

        $type = $this->type();
        return isset($alias[$type]) ? $alias[$type] : $type;
    }

    /**
     * Retourne la valeur par défaut du champ.
     *
     * @return mixed
     */
    public function defaultValue($execCallable = false) {
        $default = isset($this->value['default']) ? $this->value['default'] : null;
        if (! $execCallable || ! is_callable($default)) {
            return $default;
        }

        return call_user_func($default, $this);
    }

    /**
     * Convertit le schéma en tableau.
     *
     * @return array
     */
    public function toArray() {
        $field = $this->value;

        unset($field['fields']);
        $field['type'] = $this->friendlyType();

        // $this->fields && $field['fields'] = parent::toArray();

        if (isset($field['collection'])) {

            // Si c'est la collection standard, inutile de l'indiquer
            if ($field['collection'] === 'Docalist\Type\Collection') {
                unset($field['collection']);
                $field['type'] = isset($field['type']) ? ($field['type'] . '*') : 'text*';
            }

            else {
                $field['type'] = $field['collection'];
                unset($field['collection']);
            }
        }

        if ($field['type'] === 'text') {
            unset($field['type']);
        }

        return $field;
    }

    public function merge(array $data) {
        foreach($data as $key => $value) {
            $this->value[$key] = $value;
            if (empty($this->value[$key])) {
                unset($this->value[$key]);
            }
        }
        if (! isset($data['default'])) {
            unset($this->value['default']);
        }
    }
}